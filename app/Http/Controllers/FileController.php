<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class FileController extends Controller
{
    /**
     * Método reutilizable para guardar, actualizar y eliminar archivos.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function guardarArchivos(Request $request)
    {
        // Extraer y validar el tamaño máximo (en MB)
        $maxFileSizeMB = $request->input('max_file_size', 10); // default: 10 MB
        if (!is_numeric($maxFileSizeMB) || $maxFileSizeMB < 0.001 || $maxFileSizeMB > 500) {
            return response()->json([
                'status' => '0',
                'message' => 'El tamaño máximo de archivo no es válido. Debe estar entre 0.001 y 500 MB.',
            ]);
        }

        // Convertir MB a KB para la validación de Laravel (1 MB = 1024 KB)
        $maxFileSizeKB = $maxFileSizeMB * 1024;

        // Ahora construimos las reglas dinámicamente
        $rules = [
            'tabla' => 'required|string',
            'campo_referencia' => 'required|string',
            'id_referencia' => 'required|integer',
            'ruta' => 'required|string',
            'tipo_documento' => 'nullable|string',
            'archivos.*' => 'file|mimes:pdf,doc,docx,jpg,png,xlsx|max:' . intval($maxFileSizeKB),
            'archivos_eliminar' => 'nullable|array',
            'archivos_eliminar.*' => 'integer',
            'usuario_creador' => 'nullable|integer',
            'tipo_evidencia' => 'integer',
            'imagenOlds' => 'nullable|string',
        ];
        // Validar si se está actualizando un archivo existente
        $imagenOlds = json_decode($request->input('imagenOlds'), true);

        if (is_array($imagenOlds) && !empty($imagenOlds)) {
            foreach ($imagenOlds as $imagen) {
                DB::table('evidencia_global_files')->where('egf_id', $imagen['egf_id'])->delete();
                $ruta = public_path($imagen['egf_url'] . $imagen['egf_archivo']);

                if (file_exists($ruta)) {
                    unlink($ruta);
                }
            }
        }
        if($request->onlyEliminar){
            return response()->json([
                'status' => '1',
                'message' => 'Archivos eliminados correctamente',
            ]);
        }
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            // Buscar errores de tamaño específico
            $errores = $validator->errors();
            foreach ($errores->get('archivos.*') as $key => $mensajes) {
                foreach ($mensajes as $mensaje) {
                    if (str_contains($mensaje, 'must not be greater than') || str_contains($mensaje, 'no debe pesar más de')) {
                        return response()->json([
                            'status' => '0',
                            'message' => 'Uno de los archivos supera el tamaño máximo permitido de ' . round($maxFileSizeMB, 2) . ' MB.',
                        ]);
                    }
                }
            }

            // Error general
            return response()->json([
                'status' => '0',
                'message' => 'Errores de validación: ' . $errores->first(),
            ]);
        }

        try {
            $fechaActual = now();
            $archivosGuardados = [];

            // Crear la ruta completa para guardar los archivos
            $rutaBase = $request->tipo_documento
                ? rtrim($request->ruta, '/') . '/' . $request->tipo_documento . '/'
                : rtrim($request->ruta, '/') . '/';

            // Crear el directorio si no existe
            $fullPath = public_path($rutaBase);
            if (!File::exists($fullPath)) {
                File::makeDirectory($fullPath, 0755, true, true);
            }

            // Procesar archivos nuevos
            if ($request->hasFile('archivos')) {
                $archivos = is_array($request->file('archivos'))
                    ? $request->file('archivos')
                    : [$request->file('archivos')];

                foreach ($archivos as $archivo) {
                    // Generar un nombre único para el archivo
                    $nombreArchivo = uniqid() . '_' . $archivo->getClientOriginalName();
                    $tamanoArchivo = round($archivo->getSize() / (1024 * 1024), 2); // Tamaño en MB

                    // Verificar si el archivo ya existe para esta referencia
                    $archivoExistente = DB::table($request->tabla)
                        ->where($request->campo_referencia, $request->id_referencia)
                        ->where('egf_archivo', $archivo->getClientOriginalName())
                        ->first();

                    // Guardar el archivo en el disco
                    $archivo->move($fullPath, $nombreArchivo);

                    if ($archivoExistente) {
                        // Editar archivo existente
                        $oldPath = public_path($archivoExistente->egf_url . '/' . $archivoExistente->egf_archivo);
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }

                        // Actualizar el registro en la base de datos
                        DB::table($request->tabla)
                            ->where('egf_id', $archivoExistente->egf_id)
                            ->update(array_merge([
                                'egf_archivo' => $nombreArchivo,
                                'egf_url' => $rutaBase,
                                'egf_tamano' => $tamanoArchivo,
                                'updated_at' => $fechaActual,
                            ]));

                        $archivosGuardados[] = [
                            'egf_id' => $archivoExistente->egf_id,
                            'egf_archivo' => $nombreArchivo,
                            'egf_url' => $rutaBase,
                            'egf_tamano' => $tamanoArchivo,
                        ];
                    } else {
                        // Guardar nuevo archivo
                        $egfId = DB::table($request->tabla)->insertGetId(array_merge([
                            'egft_id' => $request->tipo_evidencia,
                            'egf_archivo' => $nombreArchivo,
                            'egf_url' => $rutaBase,
                            'egf_tamano' => $tamanoArchivo,
                            $request->campo_referencia => $request->id_referencia,
                            'created_at' => $fechaActual,
                            'updated_at' => $fechaActual,
                            'user_created' => $request->usuario_creador,
                        ]));

                        $archivosGuardados[] = [
                            'egf_id' => $egfId,
                            'egf_archivo' => $nombreArchivo,
                            'egf_url' => $rutaBase,
                            'egf_tamano' => $tamanoArchivo,
                        ];
                    }
                }
            }

            // Eliminar archivos
            if ($request->has('archivos_eliminar')) {
                $archivosEliminar = $request->archivos_eliminar;
                foreach ($archivosEliminar as $idArchivo) {
                    $archivo = DB::table($request->tabla)
                        ->where('egf_id', $idArchivo)
                        ->first();

                    if ($archivo) {
                        $oldPath = public_path($archivo->egf_url . '/' . $archivo->egf_archivo);
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }
                        DB::table($request->tabla)->where('egf_id', $idArchivo)->delete();
                    }
                }
            }

            // Respuesta exitosa
            return response()->json([
                'status' => '1',
                'message' => 'Archivos procesados correctamente',
                'files' => $archivosGuardados,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => '0',
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }
}
