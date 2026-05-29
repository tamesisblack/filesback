<?php

namespace App\Http\Controllers;

use App\Models\_14Empresa;
use App\Models\Models\Configuracion\Plataforma\Plataforma;
use App\Models\Models\Pagos\PedidosPagosHijo;
use App\Models\Models\Pagos\VerificacionPago;
use App\Models\Models\Pagos\VerificacionPagoDetalle;
use App\Models\Models\Pedidos\PedidosDocumentosLiq;
use App\Models\EmpacadoRemision;
use Illuminate\Http\Request;
use DB;
use Mockery\Undefined;

class PedidosArchivosController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    //api:Get/guardarDatosPedido
    public function index()
    {
        return "hola mundo";
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    //API:POST/guardarDatosPedido
    public function store(Request $request)
    {
        if($request->saveArchivos){
            return $this->saveArchivos($request);
        }
        if($request->saveArchivos_new){
            return $this->saveArchivos_new($request);
        }
        //PAGOS > ELIMINAR REGISTO PAGOS
        if($request->eliminarRegistroPago)       { return $this->eliminarRegistroPago($request); }
        //PAGOS > ELIMINAR VALOR PAGO EVIDENCIA
        if($request->deleteValorPago)           { return $this->deleteValorPago($request); }
    }
    public function saveArchivos($request){
        $RutaFile = null;
        if($request->archivoPago)       { $sarchivo = PedidosDocumentosLiq::findOrFail($request->id);   $RutaFile = "pedidos/pagos"; }
        if($request->archivoPagoHijo)   { $sarchivo = PedidosPagosHijo::findOrFail($request->id);       $RutaFile = "pedidos/pagos"; }
        if($request->archivoPlataforma) { $sarchivo = Plataforma::findOrFail($request->id);             $RutaFile = "configuracion/plataforma"; }
        if($request->archivoEmpresa)    { $sarchivo = _14Empresa::findOrFail($request->id);             $RutaFile = "facturacion/empresa"; }
        if($request->archivoguia)       { $sarchivo = EmpacadoRemision::findOrFail($request->id);       $RutaFile = "remision/guias"; }
        //borrar imagen anterior
        if($request->imgOld!=null || $request->imgOld!=""){
            $ruta_tmp= "archivos/".$RutaFile."/";
            $path = $ruta_tmp.$request->imgOld;
            if(file_exists($path)) {
                if(file_exists($path) ){
                    unlink($path);
                }
            }
        }
        //guardar los files
        if($request->file('archivos')){
            $files = $request->file('archivos');
            foreach($files as $clave => $file){
                $path = "/archivos/".$RutaFile;
                $filename = uniqid().$file->getClientOriginalName();
                if($file->move(public_path().$path,$filename)){
                    $sarchivo->archivo                          = $file->getClientOriginalName();
                    $sarchivo->url                              = $filename;
                    $sarchivo->save();
                }
            }
        }
    }
    public function verificarArchivo(Request $request) {
        $RutaFile = "evidenciaglobal/" . $request->Tipo;
        $path = public_path("archivos/" . $RutaFile . "/" . $request->nombreArchivo);

        if (file_exists($path)) {
            return response()->json([
                'existe' => true,
                'mensaje' => 'El archivo existe en el servidor.',
                'ruta' => $path
            ], 200);
        } else {
            return response()->json([
                'existe' => false,
                'mensaje' => 'El archivo no existe en el servidor.'
            ], 404);
        }
    }
    public function saveArchivos_new($request)
    {
        $RutaFile = "evidenciaglobal/" . $request->Tipo;

        // Borrar imagen anterior solo si imgOld está presente en el request
        if (!empty($request->imgOld)) {
            $ruta_tmp = "archivos/" . $RutaFile . "/";
            $path = $ruta_tmp . $request->imgOld;

            if (file_exists($path)) {
                unlink($path);
            }
        }

        // Guardar los archivos
        if ($request->file('archivos')) {
            $files = $request->file('archivos');
            foreach ($files as $clave => $file) {
                $path = "/archivos/" . $RutaFile;
                $filename = $request->nombrearchivo;
                $file->move(public_path() . $path, $filename);
            }
        }
    }
    public function getPagosXId($verificacion_pago_id){
        $query = DB::SELECT("SELECT pd.* ,
        CONCAT(u.nombres,' ', u.apellidos) AS distribuidor_usuario,
        dt.saldo_actual
        FROM verificaciones_pagos_detalles pd
        LEFT JOIN distribuidor_temporada dt ON pd.distribuidor_temporada_id = dt.id
        LEFT JOIN usuario u ON pd.idusuario = u.idusuario
        WHERE pd.verificacion_pago_id = ?
        ",[$verificacion_pago_id]);
        foreach($query as $key => $item){
            $this->deleteArchivoPago($item->id);
        }
    }
    public function eliminarRegistroPago($request){
        //eliminar si tuviera hijos
        $query = PedidosPagosHijo::where('documentos_liq_id',$request->doc_codigo)->get();
        $ruta_tmp= 'archivos/pedidos/pagos/';
        if(count($query) > 0){
            foreach($query as $key => $item){
                $pagoHijo = PedidosPagosHijo::findOrFail($item->id);
                $fileHijo = $pagoHijo->url;
                if($fileHijo == null || $fileHijo == "null" || $fileHijo == ""){
                }else{
                    //PROCESO
                    $path = $ruta_tmp.$fileHijo;
                    if(file_exists($path)) {
                        if(file_exists($path) ){
                            unlink($path);
                        }
                    }
                }
                $pagoHijo->delete();
            }
        }
        $pago = PedidosDocumentosLiq::findOrFail($request->doc_codigo);
        $file = $pago->url;
        if($file == null || $file == "null" || $file == ""){

        }else{
            //PROCESO
            $path = $ruta_tmp.$file;
            if(file_exists($path)) {
                if(file_exists($path) ){
                    unlink($path);
                }
            }
        }
        //eliminar detalle pagos
        DB::table('pedidos_pagos_detalles')->where('id_pago',$request->doc_codigo)->delete();
        $pago->delete();
    }
    public function deleteValorPago($request){
        return $this->deleteArchivoPago($request->id);
    }
    public function deleteArchivoPago($id){
        $dato = VerificacionPagoDetalle::find($id);
        $file = $dato->url;
        //validar si ya se pago
        $pago = VerificacionPago::findOrFail($dato->verificacion_pago_id);
        $getEstadoPago = $pago->estado;
        if($getEstadoPago == 1) { return ["status" => "0", "message" => "El registro de pago ya esta aprobado no se puede realizar cambios"]; }
        if($getEstadoPago == 2) { return ["status" => "0", "message" => "El registro de pago esta desactivado no se puede realizar cambios"]; }
        //PROCESO
        $ruta_tmp= 'archivos/pedidos/pagos/';
        $path = $ruta_tmp.$file;
        if(file_exists($path)) {
            if(file_exists($path) ){
                unlink($path);
            }
        }
        $dato->delete();
        return $dato;
    }
    //API:POST/eliminarValores
    public function eliminarValores(Request $request){
        if($request->deleteRegistroArchivo){ return $this->deleteRegistroArchivo($request); }
    }
    public function deleteRegistroArchivo($request){
        $RutaFile = null;
        if($request->archivoPlataforma) { $sarchivo = Plataforma::findOrFail($request->id);             $RutaFile = "configuracion/plataforma"; }
        if($request->archivoPagoHijo)   { $sarchivo = PedidosPagosHijo::findOrFail($request->id);       $RutaFile = "pedidos/pagos"; }
        if($request->archivoEmpresa)    { $sarchivo = _14Empresa::findOrFail($request->id);             $RutaFile = "facturacion/empresa"; }
        if($request->archivoguia)       { $sarchivo = EmpacadoRemision::findOrFail($request->id);       $RutaFile = "remision/guias"; }
        $file = $sarchivo->url;
        if($file == null || $file == ""){ }
        else{
            $path = "archivos/".$RutaFile."/".$file;
            if(file_exists($path)) {
                if(file_exists($path) ){
                    unlink($path);
                }
            }
        }

        if($RutaFile!="remision/guias"){
            $sarchivo->delete();
        }
        return $sarchivo;
    }
    /**
     * Guarda archivos físicos de evidencia de pagarés.
     * - Genera un nombre único: uniqid()_nombreoriginalsinespacio.ext
     * - Verifica que no exista ya en el directorio destino (previene colisiones)
     * - Retorna array con nombres definitivos y tamaños en MB
     *
     * POST /api/guardarArchivosPagare
     * FormData: archivos[] (multipart)
     */
    public function Save_Archivos_Pagare(Request $request)
    {
        $rutaRelativa = 'archivos/evidenciaglobal/Evidencia_de_pagares';
        $rutaFisica   = public_path('/' . $rutaRelativa);

        // Crear la carpeta si no existe
        if (!file_exists($rutaFisica)) {
            mkdir($rutaFisica, 0755, true);
        }

        if (!$request->file('archivos')) {
            return response()->json([
                'status'  => 0,
                'message' => 'No se recibieron archivos.',
            ], 400);
        }

        $files     = $request->file('archivos');
        $resultado = [];

        foreach ($files as $file) {
            $extensionOriginal = $file->getClientOriginalExtension();
            $nombreOriginal    = $file->getClientOriginalName();

            // Quitar espacios del nombre original
            $nombreSinEspacios = str_replace(' ', '', $nombreOriginal);

            // Generar nombre candidato: uniqid_nombreSinEspacios
            $nombreCandidato = uniqid() . '_' . $nombreSinEspacios;

            // Verificar unicidad en el directorio — regenerar hasta que no exista
            $intentos = 0;
            while (file_exists($rutaFisica . '/' . $nombreCandidato) && $intentos < 10) {
                $nombreCandidato = uniqid() . '_' . $nombreSinEspacios;
                $intentos++;
            }

            // Mover el archivo físico
            $file->move($rutaFisica, $nombreCandidato);

            // Calcular tamaño en MB con 2 decimales (máximo 99.99 por campo DECIMAL(4,2))
            $tamanoBytes = filesize($rutaFisica . '/' . $nombreCandidato);
            $tamanoMb    = round($tamanoBytes / (1024 * 1024), 2);

            $resultado[] = [
                'egf_archivo' => $nombreCandidato,
                'egf_url'     => $rutaRelativa,
                'egf_tamano'  => $tamanoMb,
            ];
        }

        return response()->json([
            'status'   => 1,
            'message'  => 'Archivos guardados correctamente.',
            'archivos' => $resultado,
        ]);
    }

    /**
     * Elimina un archivo físico de evidencia de pagarés.
     *
     * POST /api/eliminarArchivoPagare
     * Body: egf_archivo (nombre del archivo), egf_url (ruta relativa)
     */
    public function Eliminar_Archivo_Pagare(Request $request)
    {
        $egf_archivo = $request->input('egf_archivo');
        $egf_url     = $request->input('egf_url', 'archivos/evidenciaglobal/Evidencia_de_pagares');

        if (!$egf_archivo) {
            return response()->json(['status' => 0, 'message' => 'Nombre de archivo requerido.'], 400);
        }

        $rutaFisica = public_path($egf_url . '/' . $egf_archivo);

        if (file_exists($rutaFisica)) {
            unlink($rutaFisica);
            return response()->json(['status' => 1, 'message' => 'Archivo eliminado correctamente.']);
        }

        // Si no existe físicamente igual retornamos éxito (ya no está)
        return response()->json(['status' => 1, 'message' => 'El archivo no existía físicamente.']);
    }
    
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
