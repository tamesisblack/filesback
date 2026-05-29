<?php

namespace App\Http\Controllers;

use App\Models\AdaptacionCurricular;
use App\Models\AdaptacionFiles;
use App\Models\Articulos;
use Illuminate\Http\Request;
use App\Models\Contenido;
use App\Models\DiagnosticoPreguntas;
use App\Models\DiagnosticoPreguntasDetalle;
use App\Models\Preguntas;
use App\Models\Institucion;
use App\Models\J_contenido;
use App\Models\J_opcionesContenidos;
use App\Models\Materialcargar;
use App\Models\Materialarchivo;
use App\Models\Materialunidad;
use App\Models\Materialtema;
use App\Models\Planificacion;
use App\Models\SallePreguntas;
use App\Models\tipoJuegos;
use App\Models\FilesDepartamentos;
use App\Models\Documento;
use App\Models\Fichas;
use App\Models\FichasFiles;
use App\Models\Proyecto;
use App\Models\ProyectoAsignatura;
use App\Models\ProyectoFiles;
use App\Models\ProyectoRespuesta;
use App\Models\SeguimientoMuestra;
use App\Models\SeguimientoMuestraDetalle;
use App\Models\Juegos;
use App\Models\MaterialSubir;
use App\Models\Models\Neet\NeetTareas;
use App\Models\Models\Neet\NeetTareasFiles;
use App\Models\Models\Neet\NeetTareasRespuesta;
use App\Models\NeetUpload;
use App\Models\NeetUploadFiles;
use App\Models\NeetUploadSubTema;
use App\Models\Obsequio;
use App\Models\PedidoDocumentoAnterior;
use App\Models\Pedidos;
use App\Models\PropuestaFiles;
use App\Models\PropuestaMetologica;
use App\Models\Varios;
use App\Models\PedidoFiles;
use App\Models\PedidosV3;
use App\Models\TempFiles;
use App\Models\SallePreguntasOpcion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use stdClass;
use Mockery\Undefined;
use Illuminate\Support\Facades\Http;
class ArchivosController extends Controller
{
    public function setContenido(Request $request){
        if(!empty($request->idcontenido)){
            $file = $request->file('file');
            //RUTA LINUX
             $ruta = '/var/www/html/plataformaprolipa_files/public/tareas';

            // $ruta = public_path('/archivos/teletareas');
            //RUTA WINDOWS
           // $ruta=public_path('/tareas');
            $name = $file->getClientOriginalName();
            $url = uniqid().'.'.$file->getClientOriginalExtension();
            $ext = $file->getClientOriginalExtension();
            $file->move($ruta,$url);
            $contenido = Contenido::find($request->idcontenido)->update(
                [
                    'nombre' => $name,
                    'url' => $url,
                    'file_ext' => $ext
                ]
            );

            return [
                'nombre' => $name,
                'url' => $url,
                'file_ext' => $ext
            ];

        }else{
            $file = $request->file('file');
            //RUTA LINUX
           $ruta = '/var/www/html/plataformaprolipa_files/public/tareas';
            // $ruta = public_path('/archivos/teletareas/');

            //RUTA WINDOWS
           //$ruta=public_path('/tareas');
            $name = $file->getClientOriginalName();
            $url = uniqid().'.'.$file->getClientOriginalExtension();
            $ext = $file->getClientOriginalExtension();
            $file->move($ruta,$url);
            $contenido = new Contenido();
            $contenido->nombre = $name;
            $contenido->url = $url;
            $contenido->file_ext = $ext;
            $contenido->save();
            return $contenido;
        }
    }

    public function addTareaContenido(Request $request){
        $idusuario = $request->idusuario;
        $comentario = $request->comentario_estudiante;
        $file = $request->file('archivo');
        $ruta = '/var/www/html/plataformaprolipa_files/public/tareas';
        $fileName = uniqid().$file->getClientOriginalName();
        $file->move($ruta,$fileName);
        DB::INSERT("INSERT INTO usuario_tarea(nombre, url,tarea_idtarea,curso_idcurso,usuario_idusuario,comentario_estudiante) VALUES (?,?,?,?,?,?)",[$file->getClientOriginalName(),$fileName,$request->idtarea,$request->idcurso,$idusuario,$comentario]);
    }

    public function addContenido(Request $request){
        $file = $request->file('archivo');
        $ruta = '/var/www/html/plataformaprolipa_files/public/tareas';
        $fileName = uniqid().$file->getClientOriginalName();
        $file->move($ruta,$fileName);
        DB::INSERT("INSERT INTO contenido(nombre, url, curso_idcurso) VALUES (?,?,?)",[$file->getClientOriginalName(),$fileName,$request->idcurso]);
    }

    public function uploadDocuemntos(Request $request){
        if(empty($request->id)){
            $file = $request->file('filepond');
            $ruta = '/var/www/html/plataformaprolipa_files/public/documentos';
            $fileName = uniqid().'.'.$file->getClientOriginalExtension();;
            $file->move($ruta,$fileName);
            $contenido = new Documento();
            $contenido->url = $fileName;
            $contenido->nombre = $file->getClientOriginalName();
            $contenido->save();
            return $contenido;
        }else{

            $file = $request->file('filepond');
            $ruta = '/var/www/html/plataformaprolipa_files/public/documentos';
            $fileName = uniqid().'.'.$file->getClientOriginalExtension();;
            $file->move($ruta,$fileName);
            $contenido = new Documento();
            $contenido->url = $fileName;
            $contenido->nombre = $file->getClientOriginalName();
            $contenido->save();

            DB::SELECT('INSERT INTO `documentos_archivo` (`archivo`,`documento`) VALUES (?,?)',[$contenido->id,$request->id]);

        }
    }

    public function store(Request $request)
    {
        $datosValidados=$request->validate([
            'nombreInstitucion' => 'required',
            'telefonoInstitucion' => 'required',
            'direccionInstitucion' => 'required',
            'vendedorInstitucion' => 'required',
            'region_idregion' => 'required',
            'solicitudInstitucion' => 'required',
            // 'idciudad' => 'required',
            'tipo_institucion' => 'required',
        ]);
        if(!empty($request->idInstitucion)){
            $cambio = Institucion::find($request->idInstitucion);
            $archivo = $cambio->imgenInstitucion;
            if($request->enviarArchivo){
                //eliminar el archivo anterior si existe
                if($archivo == "" || $archivo == null || $archivo == 0){
                }else{
                    if(file_exists('archivos/instituciones_logos/'.$archivo) ){
                        unlink('archivos/instituciones_logos/'.$archivo);
                    }
                }
                $ruta = public_path('/archivos/instituciones_logos/');
                if(!empty($request->file('imagenInstitucion'))){
                    $file = $request->file('imagenInstitucion');
                    $fileName = uniqid().$file->getClientOriginalName();
                    $file->move($ruta,$fileName);
                }
                $cambio->imgenInstitucion = $fileName;
            }
        }
        else{
            $cambio = new Institucion();
            if($request->enviarArchivo){
                $ruta = public_path('/archivos/instituciones_logos/');
                if(!empty($request->file('imagenInstitucion'))){
                    $file = $request->file('imagenInstitucion');
                    $fileName = uniqid().$file->getClientOriginalName();
                    $file->move($ruta,$fileName);
                }
                $cambio->imgenInstitucion       = $fileName;
            }
        }
        $cambio->idcreadorinstitucion           = $request->idcreadorinstitucion;
        $cambio->nombreInstitucion              = $request->nombreInstitucion;
        $cambio->direccionInstitucion           = $request->direccionInstitucion;
        $cambio->telefonoInstitucion            = $request->telefonoInstitucion;
        $cambio->fecha_fundacion                = $request->fecha_fundacion == null || $request->fecha_fundacion == "null" ? null : $request->fecha_fundacion;
        $cambio->solicitudInstitucion           = $request->solicitudInstitucion;
        $cambio->codigo_institucion_milton      = $request->codigo_institucion_milton;
        $cambio->vendedorInstitucion            = $request->vendedorInstitucion;
        $cambio->tipo_institucion               = $request->tipo_institucion;
        $cambio->region_idregion                = $request->region_idregion;
        $cambio->idprovincia                    = $request->idprovincia == null || $request->idprovincia == "null" ? null : $request->idprovincia;;
        $cambio->ciudad_id                      = $request->idciudad == null || $request->idciudad == "null" ? null : $request->idciudad;
        $cambio->parr_id                        = $request->parr_id == null || $request->parr_id == "null" ? null : $request->parr_id;
        $cambio->tipo_descripcion               = $request->tipo_descripcion == null || $request->tipo_descripcion == "null" ? null : $request->tipo_descripcion;
        $cambio->estado_idEstado                = $request->estado;
        $cambio->aplica_matricula               = $request->aplica_matricula;
        $cambio->punto_venta                    = $request->punto_venta;
        $cambio->asesor_id                      = $request->asesor_id;
        $cambio->maximo_porcentaje_autorizado   = $request->maximo_porcentaje_autorizado;
        $cambio->codigo_amie                    = $request->codigo_amie;
        $cambio->save();
        return $cambio;
    }


    public function guardarLogoInstitucion(Request $request)
    {
        $cambio = Institucion::find($request->institucion_idInstitucion);

        $ruta = public_path('/archivos/instituciones_logos');
        if(!empty($request->file('archivo'))){
        $file = $request->file('archivo');
        $fileName = uniqid().$file->getClientOriginalName();
        $file->move($ruta,$fileName);
        $cambio->imgenInstitucion = $fileName;
        }

        $cambio->ideditor = $request->ideditor;
        $cambio->nombreInstitucion = $request->nombreInstitucion;
        $cambio->direccionInstitucion = $request->direccionInstitucion;
        $cambio->telefonoInstitucion = $request->telefonoInstitucion;
        $cambio->region_idregion = $request->region_idregion;
        $cambio->ciudad_id = $request->ciudad_id;
        $cambio->updated_at = now();

        $cambio->save();
        return $cambio;

    }

    public function guardaSeleccionSimple(Request $request)
    {
        // guarda pregunta
        if( $request->id_contenido_juego > 0 ){
            $contenido = J_contenido::find($request->id_contenido_juego);
        }else{
            $contenido = new J_contenido();
        }
        $ruta = public_path('/archivos/images/imagenes_juegos/seleccionSimple');
        if(!empty($request->file('img_pregunta'))){
            $file = $request->file('img_pregunta');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            $contenido->imagen  = $fileName;
        }

        $contenido->id_juego = $request->id_juego;
        $contenido->pregunta = $request->pregunta;

        $contenido->save();
        // fin guarda pregunta

        // guarda opciones de pregunta
        //OPCION1
        if( $request->id_opcion1 > 0 ){
            $respuestas = J_opcionesContenidos::find($request->id_opcion1);
        }else{
            $respuestas = new J_opcionesContenidos();
        }

        if(!empty($request->file('img_opcion1'))){
            $file = $request->file('img_opcion1');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            $respuestas->imagen_opcion = $fileName;
        }
        $respuestas->id_contenido_juegos  = $contenido->id_contenido_juego;
        $respuestas->nombre_opcion = $request->input1;
        $respuestas->tipo_opcion = $request->check1;
        $respuestas->save();

        //OPCION 2
        if( $request->id_opcion2 > 0 ){
            $respuestas = J_opcionesContenidos::find($request->id_opcion2);
        }else{
            $respuestas = new J_opcionesContenidos();
        }
        if(!empty($request->file('img_opcion2'))){
            $file = $request->file('img_opcion2');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            $respuestas->imagen_opcion = $fileName;
        }
        $respuestas->id_contenido_juegos  = $contenido->id_contenido_juego;
        $respuestas->nombre_opcion = $request->input2;
        $respuestas->tipo_opcion = $request->check2;
        $respuestas->save();

        //OPCION 3 en caso q exista
        if(!empty($request->file('img_opcion3')) || !empty($request->input3) ){
            if( $request->id_opcion3 > 0 ){
                $respuestas = J_opcionesContenidos::find($request->id_opcion3);

            }else{
                $respuestas = new J_opcionesContenidos();
            }
            if(!empty($request->file('img_opcion3'))){
                $file = $request->file('img_opcion3');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
                $respuestas->imagen_opcion = $fileName;
            }
            $respuestas->id_contenido_juegos  = $contenido->id_contenido_juego;
            $respuestas->nombre_opcion = $request->input3;
            $respuestas->tipo_opcion = $request->check3;
            $respuestas->save();
        }
        if( empty($request->file('img_opcion3')) && empty($request->input3) && $request->id_opcion3 > 0){
            $elimina = J_opcionesContenidos::find($request->id_opcion3);
            $elimina->delete();
        }

        //OPCION 4 en caso q exista
        if(!empty($request->file('img_opcion4')) || !empty($request->input4) ){
            if( $request->id_opcion4 > 0 ){
                $respuestas = J_opcionesContenidos::find($request->id_opcion4);
            }else{
                $respuestas = new J_opcionesContenidos();
            }
            if(!empty($request->file('img_opcion4'))){
                $file = $request->file('img_opcion4');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
                $respuestas->imagen_opcion = $fileName;
            }
            $respuestas->id_contenido_juegos  = $contenido->id_contenido_juego;
            $respuestas->nombre_opcion = $request->input4;
            $respuestas->tipo_opcion = $request->check4;
            $respuestas->save();
        }
        if( empty($request->file('img_opcion4')) && empty($request->input4) && $request->id_opcion4 > 0 ){
            $elimina = J_opcionesContenidos::find($request->id_opcion4);
            $elimina->delete();
        }
        //fin guarda opciones de pregunta

        return ['pregunta'=> $contenido, 'opciones'=>$respuestas];
    }

    public function storeMaterial(Request $request)
    {
      // var_dump($request->unidadestemas);
        try{
          DB::beginTransaction();
          $material = new Materialcargar;
          $material->id_libro = $request->id_libro;
          // $material->id_unidad = $request->id_unidad;
          // $material->id_tema = $request->id_tema;
          $material->save();
          //para ingresar los archivos
          $max_size = (int)ini_get('upload_max_filesize')*10240;
          // dd($request);
          $traercodigo = $this->makeid();
          $files = $request->file('archivo');
          foreach($files as $clave => $file){
            // if(Storage::putFileAs('/public/'.'material_cargar'.'/',$file,$traercodigo."".$file->getClientOriginalName())){
            //   Materialarchivo::create([
            //               "id_material" => $material->id,
            //               "archivo" => $file->getClientOriginalName(),
            //               "nombre_archivo" => $request->nombre_archivo[$clave],
            //               "url" => $traercodigo."".$file->getClientOriginalName()
            //           ]);
            //   }
              $path = "/archivos/material_cargar/";
              $filename = $traercodigo."".$file->getClientOriginalName();
               if($file->move(public_path().$path,$filename)){

              Materialarchivo::create([
                          "id_material" => $material->id,
                          "archivo" => $file->getClientOriginalName(),
                          "nombre_archivo" => $request->nombre_archivo[$clave],
                          'id_asignatura' => $request->id_asignatura,
                          "url" => $traercodigo."".$file->getClientOriginalName()
                      ]);
              }
            }
      //para ingresar a la ta temas
      $datas = $request->unidadestemas;
      $dataFinally = array();

      foreach($datas as $data){
          $data = json_decode($data);
          $temas = $data->temas; //array
          $unidad = $data->unidad; //objeto
            //para agregar en la tabla unidades
              $munidad=new Materialunidad;
              $munidad->id_material=$material->id;
              $munidad->id_unidad=$unidad->id_unidad_libro;

              $munidad->save();

          foreach($temas as $tema){
              $obj = new stdClass();
              $obj->idUnidad = $unidad->id_unidad_libro;
              $obj->idTema = $tema->id;

              array_push($dataFinally,$obj);
          }
      }
         foreach($dataFinally as $item){
          Materialtema::create([
              "id_material" =>$material->id,
              "id_tema" => $item->idTema,
              "id_unidad" => $item->idUnidad
          ]);
      }
          DB::commit();
      }catch(\Exception $e){
        return [
          // "error"=> $e,
          "message"=>"no se  pudo ingresar la informacion".'<br>'.$e,
          "status" => "0",
        ];
        // return "no se pudo ingresar el material".$e;
          DB::rollback();
      }
           return [

          "message"=>"se guardo correctamente",
          "status" => "1",
        ];
    }

    public function eliminarMaterialApoyo(Request $request){
        $archivo = Materialarchivo::findOrFail($request->id_archivo);
        $filename = $archivo->url;


        if(file_exists('archivos/material_cargar/'.$filename) ){
          unlink('archivos/material_cargar/'.$filename);
            $archivo->delete();
            return ["status" => "1", "message" => "Se elimino correctamente"];
        }else{
            return ["status" => "0", "message" => "No se pudo eliminar"];
        }

    }
    public function upload(Request $request){
        return $request;
    }
    //api:post/api/agregarMaterial
    public function agregarMaterial(Request $request){
        if(!empty($request->id)){
            $material = MaterialSubir::findOrFail($request->id);
            $archivo = $material->url;
            if($archivo ==null || $archivo == ""){
            }else{
                $ruta_tmp= "archivos/material_subir/";
                $path = $ruta_tmp.$archivo;
                if(file_exists($path)) {
                    if(file_exists($path) ){
                        unlink($path);
                    }
                }
                $material->delete();
            }

        }else{
            $material = new MaterialSubir();
        }
            $ruta=public_path('/archivos/material_subir');
            $file = $request->file('archivo');
            $name = $file->getClientOriginalName();
            $url = uniqid().'.'.$file->getClientOriginalExtension();
            $ext = $file->getClientOriginalExtension();
            $file->move($ruta,$url);
            $material->archivo   = $name;
            $material->url       = $url;
            $material->user_created = $request->usuario;
            $material->save();
            return [
                'id' => $material->id,
                'nombre' => $name,
                'url' => $url,
                'file_ext' => $ext
            ];

    }
    //api::post/api/eliminar/material/subir
    public function eliminarMaterialSubir(Request $request){
        $file = MaterialSubir::findOrFail($request->id);
        $archivo = $file->url;
        if($archivo ==null || $archivo == ""){
        }else{
            $ruta_tmp= "archivos/material_subir/";
            $path = $ruta_tmp.$archivo;
            if(file_exists($path)) {
                if(file_exists($path) ){
                    unlink($path);
                }
            }
            $file->delete();
            DB::DELETE("DELETE FROM material_subir_temas WHERE material_subir_id ='$request->id'");
            return [
                'id' => "",
                'nombre' => "",
                'url' => "",
                'file_ext' =>""
            ];
        }
    }

    public function setPlanificacion(Request $request){
        if(!empty($request->idplanificacion)){
            $file = $request->file('archivo');
            //RUTA LINUX
            $ruta = '/var/www/html/plataformaprolipa_files/public/archivos/upload/planificacion';
            //RUTA WINDOWS
           //$ruta=public_path('/archivos/upload/planificacion');
            $name = $file->getClientOriginalName();
            $url = uniqid().'.'.$file->getClientOriginalExtension();
            $ext = $file->getClientOriginalExtension();
            $file->move($ruta,$url);
            $planificacion = Planificacion::find($request->idplanificacion)->update(
                [
                    'webplanificacion' => $url,
                ]
            );

            return [
                'idplanificacion' => $request->idplanificacion,
                'nombre' => $name,
                'url' => $url,
                'file_ext' => $ext
            ];

        }else{
            $file = $request->file('archivo');
            //RUTA LINUX
            $ruta = '/var/www/html/plataformaprolipa_files/public/archivos/upload/planificacion';
            //RUTA WINDOWS
           //$ruta=public_path('/archivos/upload/planificacion');
            $name = $file->getClientOriginalName();
            $url = uniqid().'.'.$file->getClientOriginalExtension();
            $ext = $file->getClientOriginalExtension();
            $file->move($ruta,$url);
            $planificacion = new Planificacion();
            $planificacion->webplanificacion = $url;
            $planificacion->user_created = $request->usuario;
            $planificacion->save();
            return [
                'idplanificacion' => $planificacion->idplanificacion,
                'nombre' => $name,
                'url' => $url,
                'file_ext' => $ext
            ];
        }
    }

    public function eliminarPlanificacion(Request $request){
        if($request->eliminarFisico){
            $planificacion = Planificacion::findOrFail($request->idplanificacion);
            $url = $planificacion->webplanificacion;
            if(file_exists('archivos/upload/planificacion/'.$url)){
                unlink('archivos/upload/planificacion/'.$url);
            }
            $planificacion->delete();
            return "se elimino";
        }else{
             //eliminar de la carpeta tareas
            $imagenContenido = $request->imagenContenido;
            $imagenPlanificacion = $request->imagenPlanificacion;
            if(file_exists('tareas/'.$imagenContenido) ){
                unlink('tareas/'.$imagenContenido);
            }
            //eliminar de la carpeta planificaciones
            //eliminar de la carpeta tareas
            $imagenPlanificacion = $request->imagenPlanificacion;
            if(file_exists('archivos/upload/planificacion/'.$imagenPlanificacion)){
                unlink('archivos/upload/planificacion/'.$imagenPlanificacion);
            }
            $delete = DB::DELETE("DELETE FROM planificacion where idplanificacion = $request->idplanificacion");
            return "se elimino";
        }

    }

    public function storeEvaluacion(Request $request)//request datos que ingreso en los input del formulario
    {//agregar-editar

        $ruta = public_path('/archivos/img/img_preguntas');

        if( $request->id ){
            if($request->file('img_pregunta') && $request->file('img_pregunta') != null && $request->file('img_pregunta')!= 'null'){
                $file = $request->file('img_pregunta');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
                if($request->img_pregunta_old!=null || $request->img_pregunta_old!=""){
                    $ruta_tmp= 'archivos/img/img_preguntas/';
                    $path = $ruta_tmp.$request->img_pregunta_old;
                    if(file_exists($path)) {
                        if(file_exists($path) ){
                            unlink($path);
                        }
                    }
                }
            }else{
                $fileName = $request->img_pregunta_old;
                //si quitan una imagen entonces se envia vacio elimina la imagen anterior
                if($fileName == null || $fileName == "" || $fileName == "null"){
                    if($request->srcPreguntaAQuitar!=null || $request->srcPreguntaAQuitar!=""){
                        $ruta_tmp= 'archivos/img/img_preguntas/';
                        $path = $ruta_tmp.$request->srcPreguntaAQuitar;
                        if(file_exists($path)) {
                            if(file_exists($path) ){
                                unlink($path);
                            }
                        }
                    }
                    // Si es "null" como string, convertir a null real
                    $fileName = null;
                }
            }

            // Limpiar fileName si es "null" como string
            if($fileName === "null" || $fileName === "" || $fileName === "undefined"){
                $fileName = null;
            }

            $preguntas = DB::UPDATE("UPDATE `preguntas` SET `id_tema`=?,`descripcion`=?,`img_pregunta`=?, `puntaje_pregunta`=?,`estado` = ?, `idusuario`=? WHERE `id`=?", [
                $request->tema,
                $request->descripcion,
                $fileName,
                $request->puntaje_pregunta,
                $request->estado,
                $request->idusuario,
                $request->id
            ]);

            return $preguntas;
        }else{

            $pregunta = new Preguntas();

            if($request->file('img_pregunta')){
                $file = $request->file('img_pregunta');
                $ruta = public_path('/archivos/img/img_preguntas');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
            }else{
                $fileName = null; // Cambiar '' por null
            }

            $pregunta->descripcion = $request->descripcion;
            $pregunta->id_tema = $request->tema;
            $pregunta->id_tipo_pregunta = $request->id_tipo_pregunta;
            $pregunta->img_pregunta = $fileName;
            $pregunta->puntaje_pregunta = $request->puntaje_pregunta;
            $pregunta->idusuario = $request->idusuario;
            $pregunta->estado = $request->estado;
            $pregunta->grupo_user = $request->grupo_user;
            $pregunta->save();

            return $pregunta;

        }

    }
    public function eliminarPregunta(Request $request){
        //validar que no haya las preguntas en una evaluacion
        $validate = DB::SELECT("SELECT * FROM pre_evas e
        WHERE e.id_pregunta = '$request->id'
        ");
        if(count($validate) > 0){
            return ["status" => "0","message" => "No se puede eliminar la pregunta porque existe la preguntas en alguna evaluacion"];
        }
        $file = Preguntas::findOrFail($request->id);
        $archivo = $file->img_pregunta;
        if($archivo ==null || $archivo == ""){
        }else{
            $ruta_tmp= 'archivos/img/img_preguntas/';
            $path = $ruta_tmp.$archivo;
            if(file_exists($path)) {
                if(file_exists($path) ){
                    unlink($path);
                }
            }
        }
        $file->delete();
    }

    public function storeSalle(Request $request)
    {
        $ruta = public_path('/archivos/img/salle/img_preguntas');

        if( $request->id_pregunta >0 ){
            $pregunta = SallePreguntas::find($request->id_pregunta);
            if($request->file('img_pregunta') && $request->file('img_pregunta') != null && $request->file('img_pregunta')!= 'null'){
                $file = $request->file('img_pregunta');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
                if($request->img_pregunta_old!=null || $request->img_pregunta_old!=""){
                    $ruta_tmp= "archivos/img/salle/img_preguntas/";
                    $path = $ruta_tmp.$request->img_pregunta_old;
                    if(file_exists($path)) {
                        if(file_exists($path) ){
                            unlink($path);
                        }
                    }
                }
                // if( file_exists('/archivos/img/salle/img_preguntas/'.$request->img_pregunta_old) && $request->img_pregunta_old != '' ){
                //    echo "entro";
                //     unlink('/archivos/img/salle/img_preguntas/'.$request->img_pregunta_old);
                // }
            }else{
                $fileName = $request->img_pregunta_old;
            }
        }else{
            $pregunta = new SallePreguntas();
            if($request->file('img_pregunta')){
                $file = $request->file('img_pregunta');
                $ruta = public_path('/archivos/img/salle/img_preguntas');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
            }else{
                $fileName = '';
            }
        }

        $pregunta->id_tipo_pregunta     = $request->id_tipo_pregunta ;
        $pregunta->id_asignatura        = $request->id_asignatura ;
        $pregunta->descripcion          = $request->descripcion;
        $pregunta->img_pregunta         = $fileName;
        $pregunta->puntaje_pregunta     = $request->puntaje_pregunta;
        $pregunta->estado               = $request->estado;
        $pregunta->editor               = $request->editor;
        $pregunta->n_evaluacion         = $request->n_evaluacion;
        $pregunta->save();
        return $pregunta;
    }

    public function storeJuego(Request $request)
    {
        if( $request->id_tipo_juego  ){
            $juego = tipoJuegos::find($request->id_tipo_juego );
        }else{
            $juego = new tipoJuegos();
        }

        $ruta = public_path('/archivos/images/imagenes_juegos/portadas');
        if(!empty($request->file('imagen_juego'))){
            $file = $request->file('imagen_juego');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            $juego->imagen_juego  = $fileName;
        }
        $juego->nombre_tipo_juego = $request->nombre_tipo_juego;
        $juego->descripcion_tipo_juego = $request->descripcion_tipo_juego;
        $juego->estado = $request->estado;

        $juego->save();

        return $juego;
    }

    // public function perfil(Request $request)
    // {
    //     $request->validate([
    //         'nombres' => 'required|string|max:255',
    //         'apellidos' => 'required|string|max:255',
    //         'telefono' => 'nullable|string|max:20',
    //         'curso' => 'nullable|string|max:50',
    //         'paralelo' => 'nullable|string|max:10',
    //         'fecha_nacimiento' => 'nullable|date',
    //         'archivo' => 'nullable|image|max:2048', // máx 2MB, puedes ajustarlo
    //     ]);
    //     if(!empty($request->file('archivo'))){
    //         $file = $request->file('archivo');
    //         $ruta = public_path('/archivos/perfil');
    //         $fileName = uniqid().$file->getClientOriginalName();
    //         $file->move($ruta,$fileName);
    //         DB::UPDATE("UPDATE `usuario` SET `nombres`=?,`apellidos`=?,`foto_user`=?,`telefono`=?, `curso` =? ,`paralelo` = ?, `fecha_nacimiento` =?  WHERE `idusuario` = ?",
    //         [
    //             $request->nombres,
    //             $request->apellidos,
    //             $fileName,
    //             $request->telefono,
    //             $request->curso,
    //             $request->paralelo,
    //             $request->fecha_nacimiento,
    //             $request->idusuario,
    //         ]);
    //     }else{
    //         DB::UPDATE("UPDATE `usuario` SET `nombres`=?,`apellidos`=?, `telefono`=?, `curso` =? ,`paralelo` = ? , `fecha_nacimiento` =? WHERE `idusuario` = ?",
    //         [
    //             $request->nombres,
    //             $request->apellidos,
    //             $request->telefono,
    //             $request->curso,
    //             $request->paralelo,
    //             $request->fecha_nacimiento,
    //             $request->idusuario
    //         ]);
    //     }
    //     $usuario = DB::SELECT("SELECT u . *, pi.periodoescolar_idperiodoescolar, i.nombreInstitucion FROM usuario u LEFT JOIN periodoescolar_has_institucion pi ON u.institucion_idInstitucion = pi.institucion_idInstitucion JOIN institucion i ON pi.institucion_idInstitucion = i.idInstitucion WHERE u.idusuario = ? AND pi.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi WHERE phi.institucion_idInstitucion = pi.institucion_idInstitucion)",[$request->idusuario]);
    //     return $usuario;
    // }

    public function perfil(Request $request)
    {
        $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'telefono' => 'nullable|string|max:20',
            'curso' => 'nullable|string|max:50',
            'paralelo' => 'nullable|string|max:10',
            'fecha_nacimiento' => 'nullable|date_format:Y-m-d',
            'archivo' => 'nullable|image|max:2048',
        ], [
            'nombres.required' => 'El campo nombres es obligatorio.',
            'nombres.max' => 'Los nombres no pueden tener más de 255 caracteres.',
            'apellidos.required' => 'El campo apellidos es obligatorio.',
            'apellidos.max' => 'Los apellidos no pueden tener más de 255 caracteres.',
            'telefono.max' => 'El teléfono no puede tener más de 20 caracteres.',
            'curso.max' => 'El curso no puede tener más de 50 caracteres.',
            'paralelo.max' => 'El paralelo no puede tener más de 10 caracteres.',
            'fecha_nacimiento.date_format' => 'La fecha de nacimiento debe tener el formato AAAA-MM-DD.',
            'archivo.image' => 'El archivo debe ser una imagen.',
            'archivo.max' => 'La imagen no puede ser mayor a 2MB.',
        ]);


        // Normaliza campos vacíos a null
        $telefono = (trim($request->telefono) !== '' && strtolower(trim($request->telefono)) !== 'null') ? $request->telefono : null;
        $curso = (trim($request->curso) !== '' && strtolower(trim($request->curso)) !== 'null') ? $request->curso : null;
        $paralelo = (trim($request->paralelo) !== '' && strtolower(trim($request->paralelo)) !== 'null') ? $request->paralelo : null;
        $fechaNacimiento = (trim($request->fecha_nacimiento) !== '' && strtolower(trim($request->fecha_nacimiento)) !== 'null') ? $request->fecha_nacimiento : null;


        if ($request->hasFile('archivo')) {
            $file = $request->file('archivo');
            $ruta = public_path('/archivos/perfil');

            if (!file_exists($ruta)) {
                mkdir($ruta, 0755, true);
            }

            $fileName = uniqid() . $file->getClientOriginalName();
            $file->move($ruta, $fileName);

            DB::update("UPDATE `usuario`
                SET `nombres` = ?, `apellidos` = ?, `foto_user` = ?, `telefono` = ?,
                    `curso` = ?, `paralelo` = ?, `fecha_nacimiento` = ? ,`nacionalidad` =?,`sexo` =?
                WHERE `idusuario` = ?",
            [
                $request->nombres,
                $request->apellidos,
                $fileName,
                $telefono,
                $curso,
                $paralelo,
                $fechaNacimiento,
                $request->nacionalidad,
                $request->genero,
                $request->idusuario,
            ]);
        } else {
            DB::update("UPDATE `usuario`
                SET `nombres` = ?, `apellidos` = ?, `telefono` = ?,
                    `curso` = ?, `paralelo` = ?, `fecha_nacimiento` = ? ,`nacionalidad` =?,`sexo` =?
                WHERE `idusuario` = ?",
            [
                $request->nombres,
                $request->apellidos,
                $telefono,
                $curso,
                $paralelo,
                $fechaNacimiento,
                $request->nacionalidad,
                $request->genero,
                $request->idusuario,
            ]);
        }

        $usuario = DB::select("SELECT u.*, pi.periodoescolar_idperiodoescolar, i.nombreInstitucion
            FROM usuario u
            LEFT JOIN periodoescolar_has_institucion pi
                ON u.institucion_idInstitucion = pi.institucion_idInstitucion
            LEFT JOIN institucion i
                ON pi.institucion_idInstitucion = i.idInstitucion
            WHERE u.idusuario = ?
            AND pi.id = (
                SELECT MAX(phi.id)
                FROM periodoescolar_has_institucion phi
                WHERE phi.institucion_idInstitucion = pi.institucion_idInstitucion
            )",
            [$request->idusuario]
        );

        return $usuario;
    }


    public function makeid(){
        $characters = '123456789abcdefghjkmnpqrstuvwxyz';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < 5; $i++) {
            for ($i = 0; $i < 16; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
         }
    }

    public function files_departamentos_save(Request $request){

        // $archivo = new FilesDepartamentos();
        $file = $request->file('nombre_archivo');
        $ruta = public_path('/archivos/departamentos');
        $fileName = uniqid().$file->getClientOriginalName();
        $file->move($ruta, $fileName);
        // $archivo->nombre_archivo = $fileName;
        // $archivo->id_departamento = $request->id_departamento;
        // $archivo->id_usuario = $request->id_usuario;
        // $archivo->save();
        $archivo = DB::INSERT("INSERT INTO `files_archivos`(`nombre_archivo`, `id_departamento`, `estado`, `id_usuario`) VALUES (?,?,?,?)",[$fileName, $request->id_departamento, 1, $request->id_usuario]);
        return $archivo;
    }
    public function cargarOpcionDiagnostico(Request $request){
        $ruta = public_path('archivos/diagnostico/img/img_preguntas');
        if($request->file('imagen')){
            $file = $request->file('imagen');
            $ruta = public_path('archivos/diagnostico/img/img_preguntas');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
        }else{
            $fileName = '';
        }
        //validate si no esta creado el libro para las preguntas
        $validateBook = DB::SELECT("SELECT * FROM diagnostico_preguntas p
        WHERE p.libro_id = '$request->libro_id'
        ");
        if(empty($validateBook)){
            $book = new DiagnosticoPreguntas();
            $book->libro_id = $request->libro_id;
            $book->save();
        }
        $opcion = DB::INSERT("INSERT INTO
        `diagnostico_preguntas_detalle`(`pregunta`, `imagen`, `opcion`, `libro_id`)
        VALUES ('$request->pregunta', '$fileName', $request->opcion, $request->libro_id)");
        $opciones = DB::SELECT("SELECT * FROM diagnostico_preguntas_detalle WHERE libro_id = $request->libro_id
        ORDER BY created_at DESC");
        return $opciones;
    }
    public function editarOpcionDiagnostico(Request $request)
    {
        $ruta = public_path('archivos/diagnostico/img/img_preguntas');
        if($request->file('img_opcion') && $request->file('img_opcion') != null && $request->file('img_opcion')!= 'null'){
            $file = $request->file('img_opcion');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            if($request->img_opcion_old!=null || $request->img_opcion_old!=""){
                $ruta_tmp= 'archivos/diagnostico/img/img_preguntas/';
                $path = $ruta_tmp.$request->img_opcion_old;
                if(file_exists($path)) {
                    if(file_exists($path) ){
                        unlink($path);
                    }
                }
            }
        }else{
            $fileName = $request->img_opcion_old;
        }
        $opcion = DB::UPDATE("UPDATE `diagnostico_preguntas_detalle` SET `pregunta`='$request->pregunta',`imagen`='$fileName',`opcion`=$request->opcion WHERE `id`= $request->id");
        $opciones = DB::SELECT("SELECT * FROM diagnostico_preguntas_detalle WHERE libro_id = $request->libro_id
        ORDER BY created_at DESC");
        return $opciones;
    }
    public function eliminarOpcionDiagnostica(Request $request){
        $file = DiagnosticoPreguntasDetalle::findOrFail($request->id);
        $archivo = $file->imagen;
        if($archivo ==null || $archivo == ""){
        }else{
            $ruta_tmp= 'archivos/diagnostico/img/img_preguntas/';
            $path = $ruta_tmp.$archivo;
            if(file_exists($path)) {
                if(file_exists($path) ){
                    unlink($path);
                }
            }
        }
        $file->delete();
        // $opciones = DB::SELECT("SELECT * FROM diagnostico_preguntas_detalle WHERE libro_id = $request->libro_id
        // ORDER BY created_at DESC");
        // return $opciones;
    }
    public function cargarOpcion(Request $request)
    {
        $ruta = public_path('archivos/img/img_preguntas');
        $fileName = ''; // String vacío por defecto (la columna no permite NULL)

        // Guardar imagen si se envía
        if ($request->hasFile('img_opcion')) {
            $file = $request->file('img_opcion');
            $fileName = uniqid() . $file->getClientOriginalName();
            $file->move($ruta, $fileName);
        }

        // Inserción segura con bindings
        DB::insert(
            "INSERT INTO `opciones_preguntas` (`id_pregunta`, `opcion`, `img_opcion`, `tipo`, `cant_coincidencias`)
            VALUES (?, ?, ?, ?, ?)", [
                $request->id_pregunta,
                $request->opcion,
                $fileName,
                $request->tipo,
                $request->cant_coincidencias
            ]
        );

        // Recuperar opciones después de insertar
        $opciones = DB::select(
            "SELECT * FROM `opciones_preguntas` WHERE `id_pregunta` = ? ORDER BY `created_at`", [
                $request->id_pregunta
            ]
        );

        return $opciones;
    }



    public function editarOpcion(Request $request)
    {
        $ruta = public_path('archivos/img/img_preguntas');
        if($request->file('img_opcion') && $request->file('img_opcion') != null && $request->file('img_opcion')!= 'null'){
            $file = $request->file('img_opcion');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            if( file_exists('archivos/img/img_preguntas/'.$request->img_opcion_old) && $request->img_pregunta_old != '' ){
                unlink('archivos/img/img_preguntas/'.$request->img_opcion_old);
            }
        }else{
            $fileName = $request->img_opcion_old;
            // Si es string vacío, "null" o "undefined", mantener como string vacío (la columna no permite NULL)
            if($fileName === null || $fileName === "null" || $fileName === "undefined"){
                $fileName = '';
            }
        }
        
        // Usar prepared statements para preservar backslashes en LaTeX
        $opcion = DB::UPDATE("UPDATE `opciones_preguntas` SET `opcion`=?,`img_opcion`=?,`tipo`=?,`cant_coincidencias`=? WHERE `id_opcion_pregunta`= ?", [
            $request->opcion,
            $fileName,
            $request->tipo,
            $request->cant_coincidencias,
            $request->id_opcion_pregunta
        ]);
        
        $opciones = DB::SELECT("SELECT * FROM opciones_preguntas WHERE id_pregunta = ? ORDER BY created_at", [
            $request->id_pregunta
        ]);
        
        return $opciones;

    }
    //salle
    public function cargar_opcion_salle(Request $request)
    {
        $ruta = public_path('archivos/img/salle/img_preguntas');
        if($request->file('img_opcion')){
            $file = $request->file('img_opcion');
            $ruta = public_path('archivos/img/salle/img_preguntas');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
        }else{
            $fileName = '';
        }
        $opcion = new SallePreguntasOpcion();
        $opcion->id_pregunta        = $request->id_pregunta;
        $opcion->opcion             = $request->opcion;
        $opcion->img_opcion         = $fileName;
        $opcion->tipo               = $request->tipo;
        $opcion->cant_coincidencias = $request->cant_coincidencias;
        $opcion->n_evaluacion       = $request->n_evaluacion;
        $opcion->save();
        // $opcion = DB::INSERT("INSERT INTO `salle_opciones_preguntas`(`id_pregunta`, `opcion`, `img_opcion`, `tipo`, `cant_coincidencias`,`n_evaluacion`) VALUES ($request->id_pregunta, '$request->opcion', '$fileName', $request->tipo, $request->cant_coincidencias,$request->n_evaluacion)");
        $opciones = DB::SELECT("SELECT * FROM salle_opciones_preguntas WHERE id_pregunta = $request->id_pregunta ORDER BY created_at");
        return $opciones;
    }
    public function editar_opcion_salle(Request $request)
    {
        $ruta = public_path('archivos/img/salle/img_preguntas');

        if($request->file('img_opcion') && $request->file('img_opcion') != null && $request->file('img_opcion')!= 'null'){
            $file = $request->file('img_opcion');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            //borrar imagen anterior
            if($request->img_opcion_old!=null || $request->img_opcion_old!=""){
                $ruta_tmp= "archivos/img/salle/img_preguntas/";
                $path = $ruta_tmp.$request->img_opcion_old;
                if(file_exists($path)) {
                    if(file_exists($path) ){
                        unlink($path);
                    }
                }
            }
            // if( file_exists('archivos/img/salle/img_preguntas/'.$request->img_opcion_old) && $request->img_pregunta_old != '' ){
            //     unlink('archivos/img/salle/img_preguntas/'.$request->img_opcion_old);
            // }
        }else{
            $fileName = $request->img_opcion_old;
        }

        $opcion = DB::UPDATE("UPDATE `salle_opciones_preguntas` SET `opcion`='$request->opcion',`img_opcion`='$fileName',`tipo`=$request->tipo,`cant_coincidencias`=$request->cant_coincidencias,`n_evaluacion` = $request->n_evaluacion WHERE `id_opcion_pregunta`= $request->id_opcion_pregunta");

        $opciones = DB::SELECT("SELECT * FROM salle_opciones_preguntas WHERE id_pregunta = $request->id_pregunta ORDER BY created_at");

        return $opciones;

    }
     //api:get/quitar_opcion_salle/{id}
     public function quitar_opcion_salle($id){
        //delete files
        $dato = SallePreguntasOpcion::find($id);
        $file = $dato->img_opcion;
        $ruta_tmp= 'archivos/img/salle/img_preguntas/';
        $path = $ruta_tmp.$file;
        if($file == null || $file == "" || $file == "null"){
        }else{
            if(file_exists($path)) {
                if(file_exists($path) ){
                    unlink($path);
                }
            }
        }
        $dato->delete();
        return "se elimino correctamente";
    }

    public function guardarFotoMatricula(Request $request){

        $traercodigo = $this->makeid();
        $files = $request->file('archivo');
        $descripcion_periodo = $request->descripcion_periodo;
        $nombreInstitucion = $request->nombreInstitucion;

        foreach($files as $clave => $file){
            $buscarArchivo = DB::select("SELECT * from mat_estudiantes_matriculados WHERE id_estudiante = $request->id_estudiante AND id_periodo = $request->periodo_id");
            $traerarchivo = $buscarArchivo[0]->url;

            if($traerarchivo == null || $traerarchivo = ""){

            }else{
                $buscarArchivo = DB::select("SELECT * from mat_estudiantes_matriculados WHERE id_estudiante = $request->id_estudiante AND id_periodo = $request->periodo_id");
                $traerarchivo = $buscarArchivo[0]->url;
                $path = "archivos/matricula/".$nombreInstitucion.'/'.$descripcion_periodo.'/'.$traerarchivo;
                if(file_exists($path)) {
                    if(file_exists('archivos/matricula/'.$nombreInstitucion.'/'.$descripcion_periodo.'/'.$traerarchivo) ){
                        unlink('archivos/matricula/'.$nombreInstitucion.'/'.$descripcion_periodo.'/'.$traerarchivo);
                    }


                }

            }
            $path = "/archivos/matricula/".$nombreInstitucion.'/'.$descripcion_periodo.'/';
            $filename = $traercodigo."".$file->getClientOriginalName();
            if($file->move(public_path().$path,$filename)){
                //Actualizar el comprobante de la matricula
                DB::table('mat_estudiantes_matriculados')
                ->where('id_estudiante', $request->id_estudiante)
                ->where('id_periodo', $request->periodo_id)
                ->update([

                    "url" => $path.$traercodigo."".$file->getClientOriginalName(),
                    "imagen" => $file->getClientOriginalName(),

                ]);
            }

            return ["status" => "1","message" => "Se guardo correctamente el comprobante"];

        }
    }

    public function guardarComprobantepension(Request $request){


        $traercodigo = $this->makeid();
        $files = $request->file('archivo');
        $descripcion_periodo = $request->descripcion_periodo;
        $nombreInstitucion = $request->nombreInstitucion;
        $fechaPagar = $request->fecha_a_pagar;

        foreach($files as $clave => $file){
            $buscarArchivo = DB::select("SELECT * from mat_cuotas_por_cobrar WHERE id_cuotas_id = $request->idcouta");
            $traerarchivo = $buscarArchivo[0]->url;

            if($traerarchivo == null || $traerarchivo = ""){

            }else{
                $buscarArchivo = DB::select("SELECT * from mat_cuotas_por_cobrar WHERE id_cuotas_id = $request->idcouta");
                $traerarchivo = $buscarArchivo[0]->url;
                $path = "archivos/pensiones/".$nombreInstitucion.'/'.$descripcion_periodo.'/'.$traerarchivo;
                if(file_exists($path)) {
                    if(file_exists('archivos/pensiones/'.$nombreInstitucion.'/'.$descripcion_periodo.'/'.$traerarchivo) ){
                        unlink('archivos/pensiones/'.$nombreInstitucion.'/'.$descripcion_periodo.'/'.$traerarchivo);
                    }


                }

            }
            $path = "/archivos/pensiones/".$nombreInstitucion.'/'.$descripcion_periodo.'/';
            $filename = $traercodigo."".$file->getClientOriginalName();
            if($file->move(public_path().$path,$filename)){
                //Actualizar el comprobante de la matricula
                DB::table('mat_cuotas_por_cobrar')
                ->where('id_cuotas_id', $request->idcouta)

                ->update([

                    "url" => $path.$traercodigo."".$file->getClientOriginalName(),
                    "img_comprobante" => $file->getClientOriginalName(),
                    "comentario" => $request->comentarioCuota

                ]);
            }

            return ["status" => "1","message" => "Se guardo correctamente el comprobante"];

        }
    }

    //==========================INSTITUCION=======================
    public function institucion_guardar(Request $request){
        $datosValidados=$request->validate([
            'nombreInstitucion' => 'required',
            'telefonoInstitucion' => 'required',
            'direccionInstitucion' => 'required',
            'vendedorInstitucion' => 'required',
            'region_idregion' => 'required',
            'solicitudInstitucion' => 'required',
            'ciudad_id' => 'required',
            'tipo_institucion' => 'required',
        ]);
        if(!empty($request->idInstitucion)){
            // $institucion = Institucion::find($request->idInstitucion)->update($request->all());
            $cambio = Institucion::find($request->idInstitucion);
            $archivo = $cambio->imgenInstitucion;
                if($request->enviarArchivo){
                    //eliminar el archivo anterior si existe
                    if($archivo == "" || $archivo == null || $archivo == 0){
                    }else{
                        if(file_exists('archivos/instituciones_logos/'.$archivo) ){
                            unlink('archivos/instituciones_logos/'.$archivo);
                        }
                    }
                    $ruta = public_path('/archivos/instituciones_logos/');
                    if(!empty($request->file('imagenInstitucion'))){
                        $file = $request->file('imagenInstitucion');
                        $fileName = uniqid().$file->getClientOriginalName();
                        $file->move($ruta,$fileName);
                    }
                    $cambio->imgenInstitucion = $fileName;
                }
        }
        else{
            $cambio = new Institucion();
            if($request->enviarArchivo){
                $ruta = public_path('/archivos/instituciones_logos/');
                if(!empty($request->file('imagenInstitucion'))){
                    $file = $request->file('imagenInstitucion');
                    $fileName = uniqid().$file->getClientOriginalName();
                    $file->move($ruta,$fileName);
                }
            }
            $cambio->imgenInstitucion = $fileName;
        }
        $cambio->idcreadorinstitucion   = $request->idcreadorinstitucion;
        $cambio->nombreInstitucion      = $request->nombreInstitucion;
        $cambio->direccionInstitucion   = $request->direccionInstitucion;
        $cambio->telefonoInstitucion    = $request->telefonoInstitucion;
        $cambio->solicitudInstitucion   = $request->solicitudInstitucion;
        $cambio->codigo_institucion_milton   = $request->codigo_institucion_milton;
        $cambio->vendedorInstitucion    = $request->vendedorInstitucion;
        $cambio->tipo_institucion       = $request->tipo_institucion;
        $cambio->region_idregion        = $request->region_idregion;
        $cambio->ciudad_id              = $request->ciudad_id;
        $cambio->estado_idEstado        = $request->estado;
        $cambio->aplica_matricula       = $request->aplica_matricula;
        $cambio->asesor_id              = $request->asesor_id;
        $cambio->save();
        return $cambio;
    }


    //=============APIS PARA MUESTRA=============================
     public function muestra(Request $request){


        if($request->muestra_id){

            $seguimiento = SeguimientoMuestra::findOrFail($request->muestra_id);

            $seguimiento->fecha_entrega       = $request->fecha_entrega;
            $seguimiento->observacion         = $request->observacion;
            $seguimiento->persona_solicita    =$request->persona_solicita;
            if($request->admin){
                $seguimiento->usuario_editor  = $request->usuario_editor;
            }
             //si crean una insitucion temporal
             if($request->estado_institucion_temporal == 1 ){
                 $seguimiento->periodo_id = $request->periodo_id_temporal;
                 $seguimiento->institucion_id_temporal = $request->institucion_id_temporal;
                 $seguimiento->nombre_institucion_temporal = $request->nombreInstitucion;
                 $seguimiento->institucion_id = "";
             }
             if($request->estado_institucion_temporal == 0){
                 $seguimiento->institucion_id = $request->institucion_id;
                 $seguimiento->institucion_id_temporal = "";
                 $seguimiento->nombre_institucion_temporal = "";
                 //para traer el periodo
                 $buscarPeriodo = $this->traerPeriodo($request->institucion_id);
                 if($buscarPeriodo["status"] == "1"){
                     $obtenerPeriodo = $buscarPeriodo["periodo"][0]->periodo;
                     $seguimiento->periodo_id = $obtenerPeriodo;
                 }
             }


            //si envia una imagen nueva para editar
            if($request->enviarArchivo){
                  //para editar la imagen
                  $filename = $seguimiento->foto_evidencia;
                  if($filename == "" || $filename == null ){

                  }else{
                      //eliminar el archivo anterior si existe
                      if(file_exists('archivos/seguimiento/muestra/'.$filename) ){
                          unlink('archivos/seguimiento/muestra/'.$filename);
                      }
                  }
                  //para ingresar la foto de evidencia
                  $traercodigo = $this->makeid();
                  $files = $request->file('archivo');
                  foreach($files as $clave => $file){
                      $path = "/archivos/seguimiento/muestra";
                      $filename = $traercodigo."".$file->getClientOriginalName();
                      if($file->move(public_path().$path,$filename)){
                      $seguimiento->foto_evidencia = $traercodigo."".$file->getClientOriginalName();
                      }
                  }
            }


            $seguimiento->save();
            if($seguimiento){
                return ["status" => "1", "message" => "Se guardo correctamente"];
            }else{
             return ["status" => "0", "message" => "No se pudo guardar"];
            }

        }else{
            //PARA GUARDAR
               $encontrarNumeroMuestra = $this->listadoSeguimientoMuestra($request->institucion_id,$request->asesor_id,$request->periodo_id);
               if($encontrarNumeroMuestra["status"] == 0){
                 $contador = 1;
               }else{
                  $contador = $encontrarNumeroMuestra["datos"][0]->num_muestra+1;
               }
               $seguimiento = new SeguimientoMuestra();
               $seguimiento->num_muestra   = $contador;
               $seguimiento->fecha_entrega   =     $request->fecha_entrega;
               $seguimiento->observacion           = $request->observacion;
               $seguimiento->asesor_id             = $request->asesor_id;
               $seguimiento->persona_solicita      = $request->persona_solicita;
               if($request->admin){
                   $seguimiento->usuario_editor  = $request->usuario_editor;
               }
                //si crean una insitucion temporal
                if($request->estado_institucion_temporal == 1 ){
                    $seguimiento->periodo_id = $request->periodo_id_temporal;
                    $seguimiento->institucion_id_temporal = $request->institucion_id_temporal;
                    $seguimiento->nombre_institucion_temporal = $request->nombreInstitucion;
                    $seguimiento->institucion_id = "";
                }
                if($request->estado_institucion_temporal == 0){
                    $seguimiento->institucion_id = $request->institucion_id;
                    $seguimiento->institucion_id_temporal = "";
                    $seguimiento->nombre_institucion_temporal = "";
                    //para traer el periodo
                    $buscarPeriodo = $this->traerPeriodo($request->institucion_id);
                    if($buscarPeriodo["status"] == "1"){
                        $obtenerPeriodo = $buscarPeriodo["periodo"][0]->periodo;
                        $seguimiento->periodo_id = $obtenerPeriodo;
                    }
                }
                //para ingresar la foto de evidencia
                $traercodigo = $this->makeid();
                $files = $request->file('archivo');
                foreach($files as $clave => $file){
                    $path = "/archivos/seguimiento/muestra";
                    $filename = $traercodigo."".$file->getClientOriginalName();
                    if($file->move(public_path().$path,$filename)){
                    $seguimiento->foto_evidencia = $traercodigo."".$file->getClientOriginalName();
                    }
                }
               $seguimiento->save();
               //para ingresar el detalle
                $libros = $request->libro;
                //si es el verbo post
                if($request->tipoMuestra == "Crear"){
                    foreach($libros as $clave => $file){
                    SeguimientoMuestraDetalle::create([
                            "muestra_id" => $seguimiento->muestra_id,
                            "libro_id" => $request->libro[$clave],
                            'cantidad' => $request->cantidad[$clave],

                        ]);

                    }
                }

           }
           $seguimiento->save();
           if($seguimiento){
               return ["status" => "1", "message" => "Se guardo correctamente"];
           }else{
            return ["status" => "0", "message" => "No se pudo guardar"];
           }

    }


    public function listadoSeguimientoMuestra($institucion_id,$asesor_id,$periodo_id){
        $visitas = DB::SELECT("SELECT  s.* FROM seguimiento_muestra s
        WHERE s.institucion_id = '$institucion_id'
        AND s.asesor_id = '$asesor_id'
        AND s.periodo_id = '$periodo_id'
        ORDER BY s.muestra_id DESC
        ");

        if(count($visitas) == 0){
            return ["status" => "0", "message" => "No hay  seguimiento"];
        }else{
            return ["status" => "1", "message" => "No hay  seguimiento","datos" => $visitas];
        }

    }

    public function traerPeriodo($institucion_id){
        $periodoInstitucion = DB::SELECT("SELECT idperiodoescolar AS periodo , periodoescolar AS descripcion FROM periodoescolar WHERE idperiodoescolar = (
            SELECT  pir.periodoescolar_idperiodoescolar as id_periodo
            from institucion i,  periodoescolar_has_institucion pir
            WHERE i.idInstitucion = pir.institucion_idInstitucion
            AND pir.id = (SELECT MAX(phi.id) AS periodo_maximo FROM periodoescolar_has_institucion phi
            WHERE phi.institucion_idInstitucion = i.idInstitucion
            AND i.idInstitucion = '$institucion_id'))
        ");
        if(count($periodoInstitucion)>0){
            return ["status" => "1", "message"=>"correcto","periodo" => $periodoInstitucion];
        }else{
            return ["status" => "0", "message"=>"no hay periodo"];
        }
    }

    //api::post/EditarDetalle
    public function EditarDetalle(Request $request){
        if($request->devolucion){
               //update image
        //validar si nos envia foto
            $devolucion = SeguimientoMuestra::findOrfail($request->id);
            if($request->enviarArchivo){

                $filename = $devolucion->foto_devolucion;
                if($filename == "" || $filename == null ){

                }else{
                     //eliminar el archivo anterior si existe
                    if(file_exists('archivos/seguimiento/muestra/'.$filename) ){

                        unlink('archivos/seguimiento/muestra/'.$filename);
                    }

                }


                $files = $request->file('archivo');
                $traercodigo = $this->makeid();
                //editar archivo nuevo
                foreach($files as $clave => $file){
                    $path = "/archivos/seguimiento/muestra";
                    $filename = $traercodigo."".$file->getClientOriginalName();
                    if($file->move(public_path().$path,$filename)){
                        $devolucion->foto_devolucion = $traercodigo."".$file->getClientOriginalName();
                    }
                }
            }

            $devolucion->fecha_devolucion = $request->fecha_devolucion;
            $devolucion->comentario_devolucion = $request->comentario_devolucion;
            $devolucion->save();
            if($devolucion){
                return  ["status" => "1", "message" => "Se edito correctamente"];
            }else{
                return  ["status" => "0", "message" => "No se pudo editar"];
            }


        }
        $detalle = SeguimientoMuestraDetalle::findOrFail($request->id);
        $detalle->libro_id = $request->libro_id;
        $detalle->cantidad = $request->cantidad;
        $detalle->cantidad_devolucion = $request->cantidad_devolucion;
        $detalle->save();
        if($detalle){
            return  ["status" => "1", "message" => "Se edito correctamente"];
        }else{
            return  ["status" => "0", "message" => "No se pudo editar"];
        }
    }

    //para eliminar la evidencia
    public function EliminarDetalleMuestra(Request $request){
        $detalle = SeguimientoMuestraDetalle::findOrFail($request->id);
        $detalle->delete();
        return ["status" => "1", "message" => "Se elimino correctamente"];
    }

    ///CARGAR ARTICULOS PEDAGOGICOS
    public function save_articulos_ped(Request $request)
    {
        if ($request->id > 0) {
            $datos = Articulos::find($request->id);
        }else{
            $datos = new Articulos();
        }

        if(!empty($request->replace_archivo)){
            //reemplazar el archivo pedagogico
            $ruta_tmp= "archivos/articulos_pedagogicos/";
            $archivo = $request->replace_archivo;
            $path = $ruta_tmp.$archivo;
            if(file_exists($path)) {
                if(file_exists($ruta_tmp.$archivo) ){
                    unlink($ruta_tmp.$archivo);
                }
            }
        }
        if(!empty($request->replace_portada)){
            //reemplazar la portada
            $ruta_tmp= "archivos/articulos_pedagogicos/portadas/";
            $archivo = $request->replace_portada;
            $path = $ruta_tmp.$archivo;
            if(file_exists($path)) {
                if(file_exists($ruta_tmp.$archivo) ){
                    unlink($ruta_tmp.$archivo);
                }
            }
        }
        if(!empty($request->file('archivo'))){
            //guardar el archivo pedagogico
            $ruta = public_path('archivos/articulos_pedagogicos');
            $file = $request->file('archivo');
            $fileName = uniqid().'.'.$file->getClientOriginalExtension();
            $file->move($ruta,$fileName);
            $datos->nombre_archivo  = $fileName;
            $datos->ruta_archivo = 'archivos/articulos_pedagogicos';
        }

        if(!empty($request->file('portada'))){
            //guardar la portada
            $ruta_p = public_path('archivos/articulos_pedagogicos/portadas');
            $file_p = $request->file('portada');
            $fileName_p = uniqid().'.'.$file_p->getClientOriginalExtension();
            $file_p->move($ruta_p,$fileName_p);
            $datos->nombre_portada  = $fileName_p;
            $datos->ruta_portada = 'archivos/articulos_pedagogicos/portadas';
        }
        $datos->nombre = $request->nombre;
        $datos->descripcion = $request->descripcion;
        $datos->area = $request->area;
        $datos->idarea = $request->idarea;
        $datos->idusuario = $request->idusuario;
        $datos->estado = $request->estado;

        $datos->save();
        return $datos;
    }
    public function eliminar_posts(Request $request)
    {
        if(!empty($request->archivo)){
            //reemplazar el archivo pedagogico
            $ruta_tmp= "archivos/articulos_pedagogicos/";
            $archivo = $request->archivo;
            $path = $ruta_tmp.$archivo;
            if(file_exists($path)) {
                if(file_exists($ruta_tmp.$archivo) ){
                    unlink($ruta_tmp.$archivo);
                }
            }
        }
        if(!empty($request->portada)){
            //reemplazar la portada
            $ruta_tmp= "archivos/articulos_pedagogicos/portadas/";
            $archivo = $request->portada;
            $path = $ruta_tmp.$archivo;
            if(file_exists($path)) {
                if(file_exists($ruta_tmp.$archivo) ){
                    unlink($ruta_tmp.$archivo);
                }
            }
        }
        if ($request->id > 0) {
            $datos = Articulos::find($request->id);
            $datos->delete();
            return "Articulo eliminado correctamente";
        }
    }

    //==================PROYECTOS======================================
    public function guardarProyecto(Request $request){
        $asignaturas = json_decode($request->asignaturas);
        $ruta=public_path('/archivos/proyectos');
        //========SI ES RESPUESTA DEL ESTUDIANTE========
        if($request->respuesta == "1"){
              //PARA EDITAR
            if($request->id > 0){
                $proyecto = ProyectoRespuesta::findOrFail($request->id);
            //PARA GUARDAR
            }else{
                $proyecto = new ProyectoRespuesta();
                $proyecto->idusuario = $request->idusuario;
            }
            $proyecto->proyecto_id = $request->proyecto_id;
            $proyecto->curso  =$request->curso;
            $proyecto->asignatura_id = $request->asignatura_id;
        //======PARA PROYECTOS=============================
        }if($request->respuesta == "0"){
            //PARA EDITAR
              if($request->id > 0){
                $proyecto = Proyecto::findOrFail($request->id);
            //PARA GUARDAR
            }else{
                $proyecto = new Proyecto();
                $proyecto->idusuario = $request->idusuario;
                $proyecto->grupo_usuario = $request->grupo_usuario;
            }
                $proyecto->nombre       = $request->nombre;
                $proyecto->descripcion  = $request->descripcion;
                  //creditos
                if($request->creditos == "null"){
                    $proyecto->creditos = "";
                }else{
                    $proyecto->creditos = $request->creditos;
                }
        }

        //introduccion
        if($request->introduccion == "null"){
            $proyecto->introduccion = "";
        }else{
            $proyecto->introduccion = $request->introduccion;
        }
        //tarea
        if($request->tarea == "null"){
            $proyecto->tarea = "";
        }else{
            $proyecto->tarea = $request->tarea;
        }
        //proceso
        if($request->proceso == "null"){
            $proyecto->proceso = "";
        }else{
            $proyecto->proceso = $request->proceso;
        }
        //recurso
        if($request->recurso == "null"){
            $proyecto->recurso = "";
        }else{
            $proyecto->recurso = $request->recurso;
        }
        //evaluacion
        if($request->evaluacion == "null"){
            $proyecto->evaluacion = "";
        }else{
            $proyecto->evaluacion = $request->evaluacion;
        }
        //conclusion
        if($request->conclusion == "null"){
            $proyecto->conclusion = "";
        }else{
            $proyecto->conclusion = $request->conclusion;
        }

        $proyecto->save();
        if($proyecto){
              //======ARCHIVO DE DOCENTE========
            if($request->fileDocente !="" || $request->fileDocente !=null){
                $fileDocente = $request->file('fileDocente');
                $nameDocente = $fileDocente->getClientOriginalName();
                $urlDocente =  uniqid().'.'.$fileDocente->getClientOriginalExtension();
                $extDocente = $fileDocente->getClientOriginalExtension();
                $fileDocente->move($ruta,$urlDocente);
                //PARA GUARDAR EL ARCHIVO DE DOCENTE
                $archivosDocente = new ProyectoFiles();
                $archivosDocente->archivo = $nameDocente;
                $archivosDocente->url =  $urlDocente;
                $archivosDocente->tipo = "1";
                if($request->proyecto_id > 0){
                    $archivosDocente->proyecto_id = $request->proyecto_id;
                }else{
                    $archivosDocente->proyecto_id = $proyecto->id;
                }
                $archivosDocente->idusuario = $request->idusuario;
                $archivosDocente->ext = $extDocente;
                $archivosDocente->respuesta = $request->respuesta;
                $archivosDocente->curso     = $request->curso;
                $archivosDocente->save();
            }
             //=====ARCHIVO DE ESTUDIANTE========
            if($request->fileEstudiante !="" || $request->fileEstudiante !=null){
                $fileEstudiante = $request->file('fileEstudiante');
                $nameEstudiante = $fileEstudiante->getClientOriginalName();
                $urlEstudiante =  uniqid().'.'.$fileEstudiante->getClientOriginalExtension();
                $extEstudiante = $fileEstudiante->getClientOriginalExtension();
                $fileEstudiante->move($ruta,$urlEstudiante);
                //PARA GUARDAR EL ARCHIVO DEL ESTUDIANTE
                $archivosEstudiante = new ProyectoFiles();
                $archivosEstudiante->archivo = $nameEstudiante;
                $archivosEstudiante->url =  $urlEstudiante;
                $archivosEstudiante->tipo = "2";
                if($request->proyecto_id > 0){
                    $archivosEstudiante->proyecto_id = $request->proyecto_id;
                }else{
                    $archivosEstudiante->proyecto_id = $proyecto->id;
                }
                $archivosEstudiante->idusuario = $request->idusuario;
                $archivosEstudiante->ext = $extEstudiante;
                $archivosEstudiante->respuesta = $request->respuesta;
                $archivosEstudiante->curso     = $request->curso;
                $archivosEstudiante->save();
            }
            //=====ARCHIVO DE INTRODUCCION========
            if($request->fileIntroduccion !="" || $request->fileIntroduccion !=null){
                $fileIntroduccion = $request->file('fileIntroduccion');
                $nameIntroduccion = $fileIntroduccion->getClientOriginalName();
                $urlIntroduccion = uniqid().'.'.$fileIntroduccion->getClientOriginalExtension();
                $extIntroduccion = $fileIntroduccion->getClientOriginalExtension();
                $fileIntroduccion->move($ruta,$urlIntroduccion);
                //PARA GUARDAR EL ARCHIVO DEL ESTUDIANTE
                $archivosIntroduccion = new ProyectoFiles();
                $archivosIntroduccion->archivo = $nameIntroduccion;
                $archivosIntroduccion->url =  $urlIntroduccion;
                $archivosIntroduccion->tipo = "3";
                if($request->proyecto_id > 0){
                    $archivosIntroduccion->proyecto_id = $request->proyecto_id;
                }else{
                    $archivosIntroduccion->proyecto_id = $proyecto->id;
                }
                $archivosIntroduccion->idusuario = $request->idusuario;
                $archivosIntroduccion->ext = $extIntroduccion;
                $archivosIntroduccion->respuesta = $request->respuesta;
                $archivosIntroduccion->curso     = $request->curso;
                $archivosIntroduccion->save();
            }
             //=====ARCHIVO DE TAREAS========
            if($request->fileTarea !="" || $request->fileTarea !=null){
                $fileTarea = $request->file('fileTarea');
                $nameTarea = $fileTarea->getClientOriginalName();
                $urlTarea =  uniqid().'.'.$fileTarea->getClientOriginalExtension();
                $extTarea = $fileTarea->getClientOriginalExtension();
                $fileTarea->move($ruta,$urlTarea);
                //PARA GUARDAR EL ARCHIVO TAREAS
                $archivosTarea = new ProyectoFiles();
                $archivosTarea->archivo = $nameTarea;
                $archivosTarea->url =  $urlTarea;
                $archivosTarea->tipo = "4";
                if($request->proyecto_id > 0){
                    $archivosTarea->proyecto_id = $request->proyecto_id;
                }else{
                    $archivosTarea->proyecto_id = $proyecto->id;
                }
                $archivosTarea->idusuario = $request->idusuario;
                $archivosTarea->ext = $extTarea;
                $archivosTarea->respuesta = $request->respuesta;
                $archivosTarea->curso     = $request->curso;
                $archivosTarea->save();
            }
            //=====ARCHIVO DE PROCESOS========
            if($request->fileProceso !="" || $request->fileProceso !=null){
                $fileProceso = $request->file('fileProceso');
                $nameProceso = $fileProceso->getClientOriginalName();
                $urlProceso = uniqid().'.'.$fileProceso->getClientOriginalExtension();
                $extProceso = $fileProceso->getClientOriginalExtension();
                $fileProceso->move($ruta,$urlProceso);
                //PARA GUARDAR LOS ARCHIVOS DE PROCESOS
                $archivosProceso = new ProyectoFiles();
                $archivosProceso->archivo = $nameProceso;
                $archivosProceso->url =  $urlProceso;
                $archivosProceso->tipo = "5";
                if($request->proyecto_id > 0){
                    $archivosProceso->proyecto_id = $request->proyecto_id;
                }else{
                    $archivosProceso->proyecto_id = $proyecto->id;
                }
                $archivosProceso->idusuario = $request->idusuario;
                $archivosProceso->ext = $extProceso;
                $archivosProceso->respuesta = $request->respuesta;
                $archivosProceso->curso     = $request->curso;
                $archivosProceso->save();
            }
            //=====ARCHIVO DE RECURSOS========
            if($request->fileRecurso !="" || $request->fileRecurso !=null){
                $fileRecurso = $request->file('fileRecurso');
                $nameRecurso = $fileRecurso->getClientOriginalName();
                $urlRecurso = uniqid().'.'.$fileRecurso->getClientOriginalExtension();
                $extRecurso = $fileRecurso->getClientOriginalExtension();
                $fileRecurso->move($ruta,$urlRecurso);
                //PARA GUARDAR LOS ARCHIVOS DE RECURSOS
                $archivosRecurso = new ProyectoFiles();
                $archivosRecurso->archivo = $nameRecurso;
                $archivosRecurso->url =  $urlRecurso;
                $archivosRecurso->tipo = "6";
                if($request->proyecto_id > 0){
                    $archivosRecurso->proyecto_id = $request->proyecto_id;
                }else{
                    $archivosRecurso->proyecto_id = $proyecto->id;
                }
                $archivosRecurso->idusuario = $request->idusuario;
                $archivosRecurso->ext = $extRecurso;
                $archivosRecurso->respuesta = $request->respuesta;
                $archivosRecurso->curso     = $request->curso;
                $archivosRecurso->save();
            }
            //=====ARCHIVO DE EVALUACION========
            if($request->fileEvaluacion !="" || $request->fileEvaluacion !=null){
                $fileEvaluacion = $request->file('fileEvaluacion');
                $nameEvaluacion = $fileEvaluacion->getClientOriginalName();
                $urlEvaluacion = uniqid().'.'.$fileEvaluacion->getClientOriginalExtension();
                $extEvaluacion = $fileEvaluacion->getClientOriginalExtension();
                $fileEvaluacion->move($ruta,$urlEvaluacion);
                //PARA GUARDAR LOS ARCHIVOS DE EVALUACION
                $archivosEvaluacion = new ProyectoFiles();
                $archivosEvaluacion->archivo = $nameEvaluacion;
                $archivosEvaluacion->url =  $urlEvaluacion;
                $archivosEvaluacion->tipo = "7";
                if($request->proyecto_id > 0){
                    $archivosEvaluacion->proyecto_id = $request->proyecto_id;
                }else{
                    $archivosEvaluacion->proyecto_id = $proyecto->id;
                }
                $archivosEvaluacion->idusuario = $request->idusuario;
                $archivosEvaluacion->ext = $extEvaluacion;
                $archivosEvaluacion->respuesta = $request->respuesta;
                $archivosEvaluacion->curso     = $request->curso;
                $archivosEvaluacion->save();
            }
            //=====ARCHIVO DE CONCLUSION========
            if($request->fileConclusion !="" || $request->fileConclusion !=null){
                $fileConclusion = $request->file('fileConclusion');
                $nameConclusion = $fileConclusion->getClientOriginalName();
                $urlConclusion = uniqid();
                $extConclusion= uniqid().'.'.$fileConclusion->getClientOriginalExtension();
                $fileConclusion->move($ruta,$urlConclusion);
                //PARA GUARDAR LOS ARCHIVOS DE CONCLUSION
                $archivosConclusion = new ProyectoFiles();
                $archivosConclusion->archivo = $nameConclusion;
                $archivosConclusion->url =  $urlConclusion;
                $archivosConclusion->tipo = "8";
                if($request->proyecto_id > 0){
                    $archivosConclusion->proyecto_id = $request->proyecto_id;
                }else{
                    $archivosConclusion->proyecto_id = $proyecto->id;
                }
                $archivosConclusion->idusuario = $request->idusuario;
                $archivosConclusion->ext = $extConclusion;
                $archivosConclusion->respuesta = $request->respuesta;
                $archivosConclusion->curso     = $request->curso;
                $archivosConclusion->save();
            }
            if($request->respuesta == 0){
                //guardar las asignaturas
                  foreach($asignaturas as $key => $item){
                    $asignatura = new ProyectoAsignatura();
                    $asignatura->proyecto_id = $proyecto->id;
                    $asignatura->asignatura_id = $item->idasignatura;
                    $asignatura->save();
                }
            }


        }
        if($proyecto){
            return ["status" => "1", "message"=>"Proyecto guardado correctamente"];
        }else{
            return ["status" => "0","message"=> "No se pudo guardar"];
        }
    }
    //api:get>>/proyectos/file/eliminar
    public function fileEliminar(Request $request){
        //para  eliminar la asignacion al curso
        if($request->eliminarAsignacionCurso){
            $curso = DB::DELETE("DELETE FROM proyecto_curso where id = '$request->id'");
            return "se elimino la asignacion correctamente";
        }
        //para eliminar lso estudiantes que estabas asignados al curso
        if($request->eliminarEstudiantesAsignacion){
            $curso = DB::DELETE("DELETE FROM proyecto_respuesta where proyecto_id = '$request->proyecto_id' AND curso = '$request->curso_id'");
            return "se elimino los estudiantes asignados correctamente";
        }
        if($request->eliminarAsignacionCurso){
            $curso = DB::DELETE("DELETE FROM proyecto_curso where id = '$request->id'");
            return "se elimino la asignacion correctamente";
        }
        else{
            $file = ProyectoFiles::findOrFail($request->id);
            $archivo = $file->url;
            if($archivo ==null || $archivo == ""){
            }else{
                $ruta_tmp= "archivos/proyectos/";
                $path = $ruta_tmp.$archivo;
                if(file_exists($path)) {
                    if(file_exists($path) ){
                        unlink($path);
                    }
                }
                $file->delete();
                return "Se elimino correctamente";
            }
        }


    }

    //==================FIN METODOS DE PROYECTOS==========================

    //==================METODOS PARA PROPUESTAS===========================
    //api::post/guardarPropuesta
    public function guardarPropuesta(Request $request){
        $ruta=public_path('/archivos/propuestas');
        //PARA EDITAR
        if($request->id > 0){
            $propuesta = PropuestaMetologica::findOrFail($request->id);
        //PARA GUARDAR
        }else{
            $propuesta = new PropuestaMetologica();
            $propuesta->idusuario =   $request->idusuario;
        }
            $propuesta->nombre =        $request->nombre;
            $propuesta->descripcion =   $request->descripcion;
            $propuesta->estado      =   $request->estado;
            $propuesta->asignatura_id = $request->asignatura_id;
            $propuesta->grupo_usuario = $request->grupo_usuario;
            $propuesta->save();
            //========================GUARDAR LOS ARCHIVOS===========================
              //=====ARCHIVO DE UNIDAD 1========
              if($request->fileUnidad1 !="" || $request->fileUnidad1 !=null){
                $fileUnidad1 = $request->file('fileUnidad1');
                $nameUnidad1 = $fileUnidad1->getClientOriginalName();
                $urlUnidad1 = uniqid().'.'.$fileUnidad1->getClientOriginalExtension();
                $extUnidad1 = $fileUnidad1->getClientOriginalExtension();
                $fileUnidad1->move($ruta,$urlUnidad1);
                //PARA GUARDAR EL ARCHIVO
                $archivosUnidad1 = new PropuestaFiles();
                $archivosUnidad1->archivo =      $nameUnidad1;
                $archivosUnidad1->url =          $urlUnidad1;
                $archivosUnidad1->unidad =       "1";
                $archivosUnidad1->propuesta_id = $propuesta->id;
                $archivosUnidad1->ext =          $extUnidad1;
                $archivosUnidad1->save();
            }
            //=====ARCHIVO DE UNIDAD 2========
                if($request->fileUnidad2 !="" || $request->fileUnidad2){
                $fileUnidad2 = $request->file('fileUnidad2');
                $nameUnidad2 = $fileUnidad2->getClientOriginalName();
                $urlUnidad2 = uniqid().'.'.$fileUnidad2->getClientOriginalExtension();
                $extUnidad2 = $fileUnidad2->getClientOriginalExtension();
                $fileUnidad2->move($ruta,$urlUnidad2);
               //PARA GUARDAR EL ARCHIVO
                $archivosUnidad2 = new PropuestaFiles();
                $archivosUnidad2->archivo =      $nameUnidad2;
                $archivosUnidad2->url =          $urlUnidad2;
                $archivosUnidad2->unidad =       "2";
                $archivosUnidad2->propuesta_id = $propuesta->id;
                $archivosUnidad2->ext =          $extUnidad2;
                $archivosUnidad2->save();
            }
            //=====ARCHIVO DE UNIDAD 3========
            if($request->fileUnidad3 !="" || $request->fileUnidad3 !=null){
                $fileUnidad3 = $request->file('fileUnidad3');
                $nameUnidad3 = $fileUnidad3->getClientOriginalName();
                $urlUnidad3 = uniqid().'.'.$fileUnidad3->getClientOriginalExtension();
                $extUnidad3 = $fileUnidad3->getClientOriginalExtension();
                $fileUnidad3->move($ruta,$urlUnidad3);
               //PARA GUARDAR EL ARCHIVO
                $archivosUnidad3 = new PropuestaFiles();
                $archivosUnidad3->archivo =      $nameUnidad3;
                $archivosUnidad3->url =          $urlUnidad3;
                $archivosUnidad3->unidad =         "3";
                $archivosUnidad3->propuesta_id = $propuesta->id;
                $archivosUnidad3->ext =          $extUnidad3;
                $archivosUnidad3->save();
            }
            //=====ARCHIVO DE UNIDAD 4========
            if($request->fileUnidad4 !="" || $request->fileUnidad4 !=null){
                $fileUnidad4 = $request->file('fileUnidad4');
                $nameUnidad4 = $fileUnidad4->getClientOriginalName();
                $urlUnidad4 = uniqid().'.'.$fileUnidad4->getClientOriginalExtension();
                $extUnidad4 = $fileUnidad4->getClientOriginalExtension();
                $fileUnidad4->move($ruta,$urlUnidad4);
               //PARA GUARDAR EL ARCHIVO
                $archivosUnidad4 = new PropuestaFiles();
                $archivosUnidad4->archivo =      $nameUnidad4;
                $archivosUnidad4->url =          $urlUnidad4;
                $archivosUnidad4->unidad =         "4";
                $archivosUnidad4->propuesta_id = $propuesta->id;
                $archivosUnidad4->ext =          $extUnidad4;
                $archivosUnidad4->save();
            }
            //=====ARCHIVO DE UNIDAD 5========
            if($request->fileUnidad5 !="" || $request->fileUnidad5 !=null){
                $fileUnidad5 = $request->file('fileUnidad5');
                $nameUnidad5 = $fileUnidad5->getClientOriginalName();
                $urlUnidad5 = uniqid().'.'.$fileUnidad5->getClientOriginalExtension();
                $extUnidad5 = $fileUnidad5->getClientOriginalExtension();
                $fileUnidad5->move($ruta,$urlUnidad5);
               //PARA GUARDAR EL ARCHIVO
                $archivosUnidad5 = new PropuestaFiles();
                $archivosUnidad5->archivo =      $nameUnidad5;
                $archivosUnidad5->url =          $urlUnidad5;
                $archivosUnidad5->unidad =       "5";
                $archivosUnidad5->propuesta_id = $propuesta->id;
                $archivosUnidad5->ext =          $extUnidad5;
                $archivosUnidad5->save();
            }
            //=====ARCHIVO DE UNIDAD 6========
            if($request->fileUnidad6 !="" || $request->fileUnidad6){
                $fileUnidad6 = $request->file('fileUnidad6');
                $nameUnidad6 = $fileUnidad6->getClientOriginalName();
                $urlUnidad6 = uniqid().'.'.$fileUnidad6->getClientOriginalExtension();
                $extUnidad6 = $fileUnidad6->getClientOriginalExtension();
                $fileUnidad6->move($ruta,$urlUnidad6);
                //PARA GUARDAR LOS ARCHIVOS DE CONCLUSION
                $archivosUnidad6 = new PropuestaFiles();
                $archivosUnidad6->archivo =      $nameUnidad6;
                $archivosUnidad6->url =          $urlUnidad6;
                $archivosUnidad6->unidad =       "6";
                $archivosUnidad6->propuesta_id = $propuesta->id;
                $archivosUnidad6->ext =          $extUnidad6;
                $archivosUnidad6->save();
            }
            if($propuesta){
                return ["status" => "1", "message"=>"Propuesta  metodologica guardada correctamente"];
            }else{
                return ["status" => "0","message"=> "No se pudo guardar"];
            }
    }
     //api:get>>/propuesta/file/eliminar
     public function filePropuestaEliminar(Request $request){
        if($request->eliminaPropuesta){
            $propuesta = DB::DELETE("DELETE FROM propuesta_metodologicas where id = '$request->id'");
            return "se elimino la propuesta correctamente";
        }else{
            $file = PropuestaFiles::findOrFail($request->id);
            $archivo = $file->url;
            if($archivo ==null || $archivo == ""){
            }else{
                $ruta_tmp= "archivos/propuestas/";
                $path = $ruta_tmp.$archivo;
                if(file_exists($path)) {
                    if(file_exists($path) ){
                        unlink($path);
                    }
                }
                $file->delete();
                return "Se elimino correctamente";
            }
        }
    }
    //==================FIN METODOS PARA PROPUESTAS=========================================================
        //==================METODOS PARA ADAPTACIONES METODOLOGICAS===========================
    //api::post/guardarAdaptacion
    public function guardarAdaptacion(Request $request){
        $ruta=public_path('/archivos/adaptaciones');
        //PARA EDITAR
        if($request->id > 0){
            $adaptacion = AdaptacionCurricular::findOrFail($request->id);
        //PARA GUARDAR
        }else{
            $adaptacion = new AdaptacionCurricular();
            $adaptacion->idusuario      = $request->idusuario;
        }
            $adaptacion->nombre         = $request->nombre;
            $adaptacion->descripcion    = $request->descripcion;
            $adaptacion->estado         = $request->estado;
            $adaptacion->asignatura_id  = $request->asignatura_id;
            $adaptacion->grupo_usuario  = $request->grupo_usuario;
            $adaptacion->save();
            //========================GUARDAR LOS ARCHIVOS===========================
              //=====ARCHIVO DE UNIDAD 1========
              if($request->fileUnidad1 !="" || $request->fileUnidad1 !=null){
                $fileUnidad1    = $request->file('fileUnidad1');
                $nameUnidad1    = $fileUnidad1->getClientOriginalName();
                $urlUnidad1     = uniqid().'_'.$nameUnidad1;
                $extUnidad1     = $fileUnidad1->getClientOriginalExtension();
                $fileUnidad1->move($ruta,$urlUnidad1);
                //PARA GUARDAR EL ARCHIVO
                $archivosUnidad1 = new AdaptacionFiles();
                $archivosUnidad1->archivo       =  $nameUnidad1;
                $archivosUnidad1->url           =  $urlUnidad1;
                $archivosUnidad1->unidad        =  "1";
                $archivosUnidad1->adaptacion_id =  $adaptacion->id;
                $archivosUnidad1->ext =            $extUnidad1;
                $archivosUnidad1->save();
            }
            //=====ARCHIVO DE UNIDAD 2========
                if($request->fileUnidad2 !="" || $request->fileUnidad2){
                $fileUnidad2    = $request->file('fileUnidad2');
                $nameUnidad2    = $fileUnidad2->getClientOriginalName();
                $urlUnidad2     = uniqid().'_'.$nameUnidad2;
                $extUnidad2     = $fileUnidad2->getClientOriginalExtension();
                $fileUnidad2->move($ruta,$urlUnidad2);
               //PARA GUARDAR EL ARCHIVO
                $archivosUnidad2 = new AdaptacionFiles();
                $archivosUnidad2->archivo =      $nameUnidad2;
                $archivosUnidad2->url =          $urlUnidad2;
                $archivosUnidad2->unidad =       "2";
                $archivosUnidad2->adaptacion_id = $adaptacion->id;
                $archivosUnidad2->ext =          $extUnidad2;
                $archivosUnidad2->save();
            }
            //=====ARCHIVO DE UNIDAD 3========
            if($request->fileUnidad3 !="" || $request->fileUnidad3 !=null){
                $fileUnidad3 = $request->file('fileUnidad3');
                $nameUnidad3 = $fileUnidad3->getClientOriginalName();
                $urlUnidad3  = uniqid().'_'.$nameUnidad3;
                $extUnidad3  = $fileUnidad3->getClientOriginalExtension();
                $fileUnidad3->move($ruta,$urlUnidad3);
               //PARA GUARDAR EL ARCHIVO
                $archivosUnidad3 = new AdaptacionFiles();
                $archivosUnidad3->archivo       =  $nameUnidad3;
                $archivosUnidad3->url           =  $urlUnidad3;
                $archivosUnidad3->unidad        =  "3";
                $archivosUnidad3->adaptacion_id =  $adaptacion->id;
                $archivosUnidad3->ext           =  $extUnidad3;
                $archivosUnidad3->save();
            }
            //=====ARCHIVO DE UNIDAD 4========
            if($request->fileUnidad4 !="" || $request->fileUnidad4 !=null){
                $fileUnidad4 = $request->file('fileUnidad4');
                $nameUnidad4 = $fileUnidad4->getClientOriginalName();
                $urlUnidad4  = uniqid().'_'.$nameUnidad4;
                $extUnidad4  = $fileUnidad4->getClientOriginalExtension();
                $fileUnidad4->move($ruta,$urlUnidad4);
               //PARA GUARDAR EL ARCHIVO
                $archivosUnidad4 = new AdaptacionFiles();
                $archivosUnidad4->archivo       =  $nameUnidad4;
                $archivosUnidad4->url           =  $urlUnidad4;
                $archivosUnidad4->unidad        =  "4";
                $archivosUnidad4->adaptacion_id  =  $adaptacion->id;
                $archivosUnidad4->ext           =  $extUnidad4;
                $archivosUnidad4->save();
            }
            //=====ARCHIVO DE UNIDAD 5========
            if($request->fileUnidad5 !="" || $request->fileUnidad5 !=null){
                $fileUnidad5 = $request->file('fileUnidad5');
                $nameUnidad5 = $fileUnidad5->getClientOriginalName();
                $urlUnidad5  = uniqid().'_'.$nameUnidad5;
                $extUnidad5  = $fileUnidad5->getClientOriginalExtension();
                $fileUnidad5->move($ruta,$urlUnidad5);
               //PARA GUARDAR EL ARCHIVO
                $archivosUnidad5 = new AdaptacionFiles();
                $archivosUnidad5->archivo       =  $nameUnidad5;
                $archivosUnidad5->url           =  $urlUnidad5;
                $archivosUnidad5->unidad        =  "5";
                $archivosUnidad5->adaptacion_id =  $adaptacion->id;
                $archivosUnidad5->ext           =  $extUnidad5;
                $archivosUnidad5->save();
            }
            //=====ARCHIVO DE UNIDAD 6========
            if($request->fileUnidad6 !="" || $request->fileUnidad6){
                $fileUnidad6 = $request->file('fileUnidad6');
                $nameUnidad6 = $fileUnidad6->getClientOriginalName();
                $urlUnidad6  = uniqid().'_'.$nameUnidad6;
                $extUnidad6  = $fileUnidad6->getClientOriginalExtension();
                $fileUnidad6->move($ruta,$urlUnidad6);
                //PARA GUARDAR LOS ARCHIVOS DE CONCLUSION
                $archivosUnidad6 = new AdaptacionFiles();
                $archivosUnidad6->archivo       =  $nameUnidad6;
                $archivosUnidad6->url           =  $urlUnidad6;
                $archivosUnidad6->unidad        =  "6";
                $archivosUnidad6->adaptacion_id =  $adaptacion->id;
                $archivosUnidad6->ext           =  $extUnidad6;
                $archivosUnidad6->save();
            }
            if($adaptacion){
                return ["status" => "1", "message"=>"Adaptación curricular metodólogica guardada correctamente"];
            }else{
                return ["status" => "0","message"=> "No se pudo guardar"];
            }
    }
     //api:get>>/adaptacion/file/eliminar
     public function fileAdaptacionEliminar(Request $request){
        if($request->eliminaAdaptacion){
            $propuesta = DB::DELETE("DELETE FROM adaptaciones_curriculares
            where id = '$request->id'
            ");
            return "se elimino la Adaptación curricular correctamente";
        }else{
            $file = AdaptacionFiles::findOrFail($request->id);
            $archivo = $file->url;
            if($archivo ==null || $archivo == ""){
            }else{
                $ruta_tmp= "archivos/adaptaciones/";
                $path = $ruta_tmp.$archivo;
                if(file_exists($path)) {
                    if(file_exists($path) ){
                        unlink($path);
                    }
                }
                $file->delete();
                return "Se elimino correctamente";
            }
        }
    }
    //==================FIN METODOS PARA ADAPTACIONES METODOLOGICAS=========================================================

    public function get_rompecabezas($id_juego)
    {
        $rompecabezas = DB::SELECT("SELECT * FROM `j_contenido_juegos` WHERE `estado` = 1 AND `id_juego` = $id_juego");
        return $rompecabezas;
    }

    public function delete_rompecabezas($id)
    {
        DB::DELETE("UPDATE `j_contenido_juegos` SET `estado`=0 WHERE `id_contenido_juego` = $id");
    }

    public function save_rompecabezas(Request $request)
    {
        $ruta = public_path('/archivos/images/imagenes_juegos/rompecabezas/');
        if( $request->id != '' ){

            if($request->file('img_portada') && $request->file('img_portada') != null && $request->file('img_portada')!= 'null'){
                $file = $request->file('img_portada');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
                if( file_exists('/archivos/images/imagenes_juegos/rompecabezas/'.$request->img_old) && $request->img_old != '' ){
                    unlink('/arhivos/images/imagenes_juegos/rompecabezas/'.$request->img_old);
                }
            }else{
                $fileName = $request->img_old;
            }


            $contenido = J_contenido::find($request->id);

            $contenido->id_juego    = $request->id_juego;
            $contenido->imagen      = $fileName;
            $contenido->descripcion = $request->titulo;

            $contenido->save();

            return $contenido;
        }else{

            if($request->file('img_portada')){
                $file = $request->file('img_portada');
                $ruta = public_path('/archivos/images/imagenes_juegos/rompecabezas/');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta, $fileName);
            }else{
                $fileName = '';
            }

            DB::INSERT("INSERT INTO `j_contenido_juegos`(`id_juego`, `imagen`, `descripcion`) VALUES (?,?,?)", [$request->id_juego, $fileName, $request->titulo]);

            return "creado";
        }
    }



    public function j_guardar_calificacion(Request $request)
    {
        $califica = DB::INSERT("INSERT INTO  j_calificaciones(id_juego, codigo_curso, id_usuario, calificacion) VALUES (?,?,?,?)", [$request->id_juego, $request->codigo_curso, $request->id_usuario, $request->calificacion]);

        return $califica;
    }
    public function guardarPedido(Request $request){
        //validar un pedido de institucion por periodo solo para cuando va a guardar no el editar
        if( $request->id_pedido ){
        }
        $ruta = public_path('archivos/pedidos/img/img_cedula');
        if(empty($validate)){
            ///imagen
            if($request->file('imagen') && $request->file('imagen') != null && $request->file('imagen')!= 'null'){
                $file = $request->file('imagen');
                $fileName = uniqid().$file->getClientOriginalName();
                $file->move($ruta,$fileName);
                if($request->img_opcion_old!=null || $request->img_opcion_old!=""){
                    $ruta_tmp= 'archivos/pedidos/img/img_cedula/';
                    $path = $ruta_tmp.$request->img_opcion_old;
                    //ELIMINAR SOLO SI NO SE ESTA USANDO EN OTRO PEDIDO
                    $query = DB::SELECT("SELECT  * FROM pedidos_documentos_docentes pd
                        WHERE
                        (pd.doc_cedula = '$request->img_opcion_old'
                        OR pd.doc_ruc = '$request->img_opcion_old'
                        )
                    ");
                    if(empty($query)){
                        if(file_exists($path)) {
                            if(file_exists($path) ){
                                unlink($path);
                            }
                        }
                    }
                }
            }else{
                $fileName = $request->img_opcion_old;
            }
            //fin imagen
            ///RUC
            $fileNameRuc = "";
            if($request->file('doc_ruc') && $request->file('doc_ruc') != null && $request->file('doc_ruc')!= 'null'){
                $file_ruc = $request->file('doc_ruc');
                $fileNameRuc = uniqid().$file_ruc->getClientOriginalName();
                $file_ruc->move($ruta,$fileNameRuc);
                if($request->img_opcion_old_ruc!=null || $request->img_opcion_old_ruc!=""){
                    $ruta_tmp= 'archivos/pedidos/img/img_cedula/';
                    $path_ruc = $ruta_tmp.$request->img_opcion_old_ruc;
                    //ELIMINAR SOLO SI NO SE ESTA USANDO EN OTRO PEDIDO
                    $query = DB::SELECT("SELECT  * FROM pedidos_documentos_docentes pd
                        WHERE
                        (pd.doc_cedula = '$request->img_opcion_old_ruc'
                        OR pd.doc_ruc = '$request->img_opcion_old_ruc'
                        )
                    ");
                    if(empty($query)){
                        if(file_exists($path_ruc)) {
                            if(file_exists($path_ruc) ){
                                unlink($path_ruc);
                            }
                        }
                    }
                }
            }else{
                $fileNameRuc = $request->img_opcion_old_ruc;
            }
            //fin RUC
            if( $request->id_pedido ){
                $pedido = Pedidos::find($request->id_pedido);

            }else{
                $pedido = new Pedidos();
            }
            // if($request->anticipo == "null" || $request->anticipo == null){
            //     $pedido->anticipo           = null;
            // }else{
            //     $pedido->anticipo           = $request->anticipo;
            // }
            //CEDULA
            if($fileName == "null" || $fileName == null || $fileName == 'undefined'){
                $pedido->imagen             = null;
            }else{
                $pedido->imagen             = $fileName;
            }
            //RUC
            if($fileNameRuc == "null" || $fileNameRuc == null || $fileNameRuc == 'undefined'){
                $pedido->doc_ruc            = null;
            }else{
                $pedido->doc_ruc            = $fileNameRuc;
            }
            $pedido->ifanticipo             = $request->ifanticipo;
            $pedido->anticipoAsesor             = $request->anticipoAsesor;
            // $pedido->porcentaje_anticipo    = $request->porcentaje_anticipo;
            // if($request->convenio_anios == "null" || $request->convenio_anios == null || $request->convenio_anios == 0){
            //     $pedido->convenio_anios     = null;
            // }else{
            //     $pedido->convenio_anios     = $request->convenio_anios;
            // }
            $pedido->save();
            return response()->json(['pedido' => $pedido, 'error' => ""]);
        }else{
            return ["status" => "0", "message" => "Ya ha sido generado un pedido con esa institución en este período"];
        }
    }
    public function guardarDocumentosDespuesContrato(Request $request){
        $ifversion3 = $request->ifversion3;
        $ruta = public_path('archivos/pedidos/img/img_cedula');
        if( $ifversion3 ){
            $pedido = PedidosV3::find($request->id_pedido);
        }else{
            $pedido = Pedidos::find($request->id_pedido);
        }
        $id_periodo = $pedido->id_periodo;
        ///imagen
        if($request->file('imagen') && $request->file('imagen') != null && $request->file('imagen')!= 'null'){
            $file = $request->file('imagen');
            $fileName = uniqid().$file->getClientOriginalName();
            $file->move($ruta,$fileName);
            if($request->img_opcion_old!=null || $request->img_opcion_old!=""){
                $ruta_tmp= 'archivos/pedidos/img/img_cedula/';
                //ELIMINAR SOLO SI NO SE ESTA USANDO EN OTRO PEDIDO
                $query = DB::SELECT("SELECT  * FROM pedidos_documentos_docentes pd
                    WHERE
                    (pd.doc_cedula = ?
                    OR pd.doc_ruc = ?
                    )
                    AND pd.id_periodo <> ?
                ", [$request->img_opcion_old, $request->img_opcion_old, $id_periodo]);
                if(empty($query)){
                    $path = $ruta_tmp.$request->img_opcion_old;
                    if(file_exists($path)) {
                        if(file_exists($path) ){
                            unlink($path);
                        }
                    }
                }
            }
        }else{
            $fileName = $request->img_opcion_old;
        }
        //fin imagen
        ///RUC
        $fileNameRuc = "";
        if($request->file('doc_ruc') && $request->file('doc_ruc') != null && $request->file('doc_ruc')!= 'null'){
            $file_ruc = $request->file('doc_ruc');
            $fileNameRuc = uniqid().$file_ruc->getClientOriginalName();
            $file_ruc->move($ruta,$fileNameRuc);
            if($request->img_opcion_old_ruc!=null || $request->img_opcion_old_ruc!=""){
                $ruta_tmp= 'archivos/pedidos/img/img_cedula/';
                $path_ruc = $ruta_tmp.$request->img_opcion_old_ruc;
                //ELIMINAR SOLO SI NO SE ESTA USANDO EN OTRO PEDIDO
                $query = DB::SELECT("SELECT  * FROM pedidos_documentos_docentes pd
                  WHERE
                  (pd.doc_cedula = ?
                  OR pd.doc_ruc = ?
                  )
                  AND pd.id_periodo <> ?
                ", [$request->img_opcion_old_ruc, $request->img_opcion_old_ruc, $id_periodo]);
                if(empty($query)){
                    if(file_exists($path_ruc)) {
                        if(file_exists($path_ruc) ){
                            unlink($path_ruc);
                        }
                    }
                }
            }
        }else{
            $fileNameRuc = $request->img_opcion_old_ruc;
        }

        //CEDULA
        if($fileName == "null" || $fileName == null || $fileName == 'undefined'){
            $pedido->imagen             = null;
        }else{
            $pedido->imagen             = $fileName;
        }
        //RUC
        if($fileNameRuc == "null" || $fileNameRuc == null || $fileNameRuc == 'undefined'){
            $pedido->doc_ruc            = null;
        }else{
            $pedido->doc_ruc            = $fileNameRuc;
        }
        $pedido->save();
        if( $ifversion3 ){
            $this->updateDocumentoAnteriorV3($request->id_pedido);
        }else{
            $this->updateDocumentoAnterior($request->id_pedido,1);
        }
        return response()->json(['pedido' => $pedido, 'error' => ""]);
    }
    //api:Get/updateDocumentoAnterior/{id_pedido}/{withContrato}
    public function updateDocumentoAnterior($id_pedido,$withContrato){
        ///validar que el pedido exista y este activo
        $query = DB::SELECT("SELECT p.*, u.cedula,
        CONCAT(u.nombres, ' ', u.apellidos) AS docente
        FROM pedidos p
        LEFT JOIN usuario u ON p.id_responsable = u.idusuario
        WHERE p.id_pedido = '$id_pedido'
        AND p.tipo = '0'
        AND p.estado = '1'
        -- AND p.ifanticipo  = '1'
        AND p.imagen IS NOT NULL
        ");
        if(sizeOf($query) > 0){
            //variables
            $institucion                = $query[0]->id_institucion;
            $id_periodo                 = $query[0]->id_periodo;
            $cedulaDocente              = $query[0]->cedula;
            $doc_cedula                 = $query[0]->imagen;
            $doc_ruc                    = $query[0]->doc_ruc;

            //validar si existe edito si no guardo
            $validate = DB::SELECT("SELECT  * FROM pedidos_documentos_docentes pd
                WHERE pd.institucion_id = '$institucion'
                AND pd.id_periodo       = '$id_periodo'
                ORDER BY pd.id DESC
            ");
            if(empty($validate)){
                //Guardar
                $documento              = new PedidoDocumentoAnterior();
            }
            else{
                $id                     = $validate[0]->id;
                //Editar
                $documento              = PedidoDocumentoAnterior::findOrFail($id);
            }
            $documento->institucion_id  = $institucion;
            $documento->cedula          = $cedulaDocente;
            $documento->doc_cedula      = $doc_cedula;
            $documento->doc_ruc         = $doc_ruc;
            $documento->id_periodo      = $id_periodo;
            $documento->save();
            if($documento){
                return ["status" => "1","message" => "Se guardo correctamente"];
            }else{
                return ["status" => "0","message" => "No se pudo guardar"];
            }
        }
        return ["status" => "0","message" => "-"];
    }
    public function updateDocumentoAnteriorV3($id_pedido){
        ///validar que el pedido exista y este activo
        $query = DB::SELECT("SELECT p.*
        -- u.cedula, CONCAT(u.nombres, ' ', u.apellidos) AS docente
        FROM pedidosv3 p
        WHERE p.id_pedido = '$id_pedido'
        AND p.tipo = '0'
        AND p.estado = '1'
        ");
        if(sizeOf($query) > 0){
            //variables
            $institucion                = $query[0]->institucion_id;
            // $cedulaDocente              = $query[0]->cedula;
            $doc_cedula                 = $query[0]->imagen;
            $doc_ruc                    = $query[0]->doc_ruc;
            $id_periodo                 = $query[0]->id_periodo;
            //validar si existe edito si no guardo
            $validate = DB::SELECT("SELECT  * FROM pedidos_documentos_docentes pd
                WHERE pd.institucion_id = '$institucion'
                AND pd.id_periodo       = '$id_periodo'
                ORDER BY pd.id DESC
            ");
            if(empty($validate)){
                //Guardar
                $documento              = new PedidoDocumentoAnterior();
            }
            else{
                $id                     = $validate[0]->id;
                //Editar
                $documento              = PedidoDocumentoAnterior::findOrFail($id);
            }
            $documento->institucion_id  = $institucion;
            // $documento->cedula_docente  = $cedulaDocente;
            $documento->doc_cedula      = $doc_cedula;
            $documento->doc_ruc         = $doc_ruc;
            $documento->id_periodo      = $id_periodo;
            $documento->save();
            // si doc_cedula y doc_ruc son nulos eliminar el registro
            if( ($doc_cedula == null || $doc_cedula == '') && ($doc_ruc == null || $doc_ruc == '') ){
                $documento->delete();
            }
            if($documento){
                return ["status" => "1","message" => "Se guardo correctamente"];
            }else{
                return ["status" => "0","message" => "Se guardo correctamente"];
            }
        }
        return ["status" => "0","message" => "-"];
    }
    public function guardarFilePedido(Request $request){
           //======ARCHIVO DE EVIDENCIA GERENCIA========
           $ruta=public_path('/archivos/pedidos/img/aprobados');
           if($request->fileEvidenciaGerente !="" || $request->fileEvidenciaGerente !=null){
            $fileGerente = $request->file('fileEvidenciaGerente');
            $nameGerente = $fileGerente->getClientOriginalName();
            $urlGerente =  uniqid().'.'.$fileGerente->getClientOriginalName();
            $fileGerente->move($ruta,$urlGerente);
            //PARA GUARDAR EL ARCHIVO DE DOCENTE
            $archivo = new PedidoFiles();
            $archivo->archivo       = $nameGerente;
            $archivo->url           = $urlGerente;
            $archivo->tipo          = $request->tipo;
            $archivo->id_pedido     = $request->id_pedido;
            $archivo->user_created  = $request->user_created;
            $archivo->save();
            return $archivo;
        }
    }
    public function filePedidoEliminar(Request $request){
        $file = PedidoFiles::findOrFail($request->id);
        $archivo = $file->url;
        if($archivo ==null || $archivo == ""){
        }else{
            $ruta_tmp= "archivos/pedidos/img/aprobados/";
            $path = $ruta_tmp.$archivo;
            if(file_exists($path)) {
                if(file_exists($path) ){
                    unlink($path);
                }
            }
            $file->delete();
            return "Se elimino correctamente";
        }
    }
    public function documentosPedidoEliminar(Request $request){
        DB::beginTransaction();
        try {
            $pedido         = PedidosV3::find($request->id_pedido);
            if(!$pedido){
                return ["status" => "0", "message" => "Pedido no encontrado"];
            }

            $archivo        = $request->nombre_archivo;
            $tipo_documento = $request->tipo_documento;
            $ruta_tmp       = "archivos/pedidos/img/img_cedula/";
            $path           = $ruta_tmp.$archivo;

            if($archivo ==null || $archivo == ""){
                return ["status" => "0", "message" => "No se especificó el archivo a eliminar"];
            }

            // validar si es cedula o ruc estan en otro periodo no eliminar
            //ELIMINAR SOLO SI NO SE ESTA USANDO EN OTRO PEDIDO
            $query = DB::SELECT("SELECT  * FROM pedidos_documentos_docentes pd
                WHERE
                (pd.doc_cedula = ?
                OR pd.doc_ruc = ?
                )
                AND pd.id_periodo <> ?
            ", [$pedido->imagen, $pedido->doc_ruc, $pedido->id_periodo]);

            if(empty($query)){
                if(file_exists($path)) {
                    unlink($path);
                }
            }

            // actualizar pedido
            if($tipo_documento == 'imagen'){
                $pedido->imagen = null;
            }
            if($tipo_documento == 'doc_ruc'){
                $pedido->doc_ruc = null;
            }
            $pedido->save();

            // actualizar PedidoDocumentoAnterior
            $this->updateDocumentoAnteriorV3($request->id_pedido);

            DB::commit();
            return ["status" => "1", "message" =>"Se eliminó correctamente"];

        } catch (\Exception $e) {
            DB::rollback();
            return ["status" => "0", "message" => "Error al eliminar: " . $e->getMessage()];
        }
    }
    public function changeEstadoHistoricoPedido(Request $request){
        $fechaActual = null;
        //si envia contabilidad para recibir
        if($request->recibido){
            $fechaActual = $request->fromDateConta;
            $respuesta =  DB::UPDATE("UPDATE pedidos_historico
            SET `$request->campo_fecha` = '$fechaActual',
            `estado` = '$request->estado'
            WHERE `id_pedido` = '$request->id_pedido'
            ");
            if($respuesta){
                return ["status" => "1", "message" =>"Se guardo correctamente"];
            }else{
                return ["status" => "0","message" => "No se pudo guardar"];
            }
        }
        //si envia contabilidad
        if($request->contabilidad == 'yes'){
            $fechaActual = $request->fromDateConta;
        }else{
            $fechaActual = date('Y-m-d H:i:s');
        }
        if($request->NoCambios){
            return ["status" => "1", "message" =>"Se guardo correctamente"];
        }else{
            //borrar imagen anterior
            if($request->imgOld!=null || $request->imgOld!=""){
                $ruta_tmp= "archivos/pedidos/".$request->tipoDocumento.'/';
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
                    $path = "/archivos/pedidos/".$request->tipoDocumento;
                    $filename = uniqid().$file->getClientOriginalName();
                    if($file->move(public_path().$path,$filename)){
                    $respuesta =  DB::UPDATE("UPDATE pedidos_historico
                    SET `$request->campo_fecha` = '$fechaActual',
                    `$request->campo_file` = '$filename',
                        `estado` = '$request->estado'
                        WHERE `id_pedido` = '$request->id_pedido'
                        ");
                    }
                }
            }
            if($respuesta){
                return ["status" => "1", "message" =>"Se guardo correctamente"];
            }else{
                return ["status" => "0","message" => "No se pudo guardar"];
            }
        }
    }

    public function guardar_institucines_base_milton(){ /// instituciones de prolipa en base de milton DEBEN TENER EL ID DE CIUDAD CORRECTO
        set_time_limit(6000000);
        ini_set('max_execution_time', 6000000);
        $instituciones = DB::SELECT("SELECT i.*, c.id_ciudad_milton FROM institucion i, ciudad c WHERE i.ciudad_id = c.idciudad AND i.codigo_institucion_milton IS NULL AND c.id_ciudad_milton IS NOT NULL;");
        foreach ($instituciones as $key => $value) {
            try {
                $form_data = [
                    'ciu_codigo'     => intval($value->id_ciudad_milton),
                    'tip_ins_codigo' => 2, // por defecto particulares
                    'cic_codigo'     => 1, // por defecto ??
                    'ins_nombre'     => $value->nombreInstitucion,
                    'ins_direccion'  => $value->direccionInstitucion,
                    'ins_telefono'   => $value->telefonoInstitucion,
                    'ins_ruc'        => '', // no tienen
                    'ins_sector'     => '', // no tienen
                ];
                $institucion = Http::post('http://186.46.24.108:9095/api/Escuela', $form_data);
                $json_institucion = json_decode($institucion, true);
                // guardar en base de prolipa tabla institucion
                if( count($json_institucion) > 0 ){
                    $query = "UPDATE `institucion` SET `codigo_institucion_milton`='".$json_institucion['ins_codigo']."' WHERE `idInstitucion` = ".$value->idInstitucion.";";
                    DB::SELECT($query);
                    dump($query);
                }

            } catch (\Throwable $th) {
                dump($th);
            }
        }
    }

    public function f_guardarVarios(Request $request)
    {
        //guardar los files
        $ruta = "/archivos/files_varios_libre/";
        $file = $request->file('imagen');
        $filename = "";
        if($request->file('imagen') && $request->file('imagen') != null && $request->file('imagen')!= 'null' && $request->file('imagen')!= 'undefined'){
            $filename = uniqid().$file->getClientOriginalName();
            $file->move(public_path().$ruta,$filename);
            if($request->img_opcion_old!=null || $request->img_opcion_old!=""){
                $ruta_tmp= 'archivos/files_varios_libre/';
                $path = $ruta_tmp.$request->img_opcion_old;
                if(file_exists($path)) {
                    if(file_exists($path) ){
                        unlink($path);
                    }
                }
            }
        }else{
            $filename = $request->img_opcion_old;
        }
        if($filename == "null" || $filename == null){
            $filename = "";
        }
         $campos = [
            'titulo' => $request->titulo ,
            'descripcion' => $request->descripcion ,
            'url' => $request->url ,
            'orden' => $request->orden,
            'imagen' => $filename,
            'estado' => $request->estado
        ];

        if ($request->id > 0) {
            $dato = DB::table('varios')
            ->where('id',$request->id)
            ->update($campos);
        }else{
            $dato = DB::table('varios')
            ->insert($campos);
        }
        return $dato;
    }
    public function f_deleteVarios(Request $request){
        $dato = Varios::find($request->id);
        $file = $dato->imagen;
        $ruta_tmp= 'archivos/files_varios_libre/';
        $path = $ruta_tmp.$file;
        if(file_exists($path)) {
            if(file_exists($path) ){
                unlink($path);
            }
        }
        $dato->delete();
        return $dato;
    }
    public function changeEstadoObsequio(Request $request){
        $fechaActual = null;
        $fechaActual = date('Y-m-d H:i:s');
        if($request->NoCambios){
            return ["status" => "1", "message" =>"Se guardo correctamente"];
        }else{
            //borrar imagen anterior
            if($request->imgOld!=null || $request->imgOld!=""){
                $ruta_tmp= "archivos/obsequios/".$request->tipoDocumento.'/';
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
                    $path = "/archivos/obsequios/".$request->tipoDocumento;
                    $filename = uniqid().$file->getClientOriginalName();
                    if($file->move(public_path().$path,$filename)){
                    $respuesta =  DB::UPDATE("UPDATE obsequios
                    SET `$request->campo_fecha` = '$fechaActual',
                    `$request->campo_file` = '$filename',
                        `estado` = '$request->estado'
                        WHERE `id` = '$request->id_pedido'
                        ");
                    }
                }
            }
            if($respuesta){
                return ["status" => "1", "message" =>"Se guardo correctamente"];
            }else{
                return ["status" => "0","message" => "No se pudo guardar"];
            }
        }
    }
    public function saveCotizacionFactura(Request $request){
        //variables
        $fechaActual = null;
        $fechaActual = date('Y-m-d H:i:s');
        $filesC = $request->file('cotizacion');
        $foto_cotizacion = $filesC[0];
        $filesF = $request->file('factura');
        $foto_factura = $filesF[0];
        //guardar los files
        //cotizacion
        $pathC = "/archivos/obsequios/cotizacion";
        $filenameC = uniqid().$foto_cotizacion->getClientOriginalName();
        $foto_cotizacion->move(public_path().$pathC,$filenameC);
        //factura
        $pathF = "/archivos/obsequios/factura";
        $filenameF = uniqid().$foto_factura->getClientOriginalName();
        $foto_factura->move(public_path().$pathF,$filenameF);
        //proceso
        $obsequio = Obsequio::findOrFail($request->id);
        $obsequio->foto_cotizacion                       = $filenameC;
        $obsequio->foto_factura                          = $filenameF;
        $obsequio->estado                                = $request->estado;
        $obsequio->fecha_administrador_sube_cotizacion   = $fechaActual;
        $obsequio->valor_total                           = $request->valor_total;
        $obsequio->valor_disponible                      = $obsequio->valor_obsequios - $request->valor_total;
        $obsequio->save();
        if($obsequio){
            return ["status" => "1", "message" =>"Se guardo correctamente"];
        }else{
            return ["status" => "0","message" => "No se pudo guardar"];
        }
    }
    //METODOS DE FICHAS
    public function saveFichas(Request $request){
        if ($request->id > 0) {
            $dato = Fichas::find($request->id);
        }else{
            $dato = new Fichas();
        }
        $dato->titulo           = $request->titulo;
        $dato->tipo             = $request->tipo;
        $dato->estado           = $request->estado;
        $dato->id_asignatura    = $request->id_asignatura;
        $dato->id_unidad        = $request->id_unidad;
        $dato->periodo_id       = $request->periodo_id;
        $dato->save();
        //si no envia files
        if($request->noSendFiles){
        }
        //si envia files
        else{
            //guardar los files
            if($request->file('archivos')){
                $files = $request->file('archivos');
                foreach($files as $clave => $file){
                    $path = "/archivos/fichas/";
                    $filename = uniqid().$file->getClientOriginalName();
                    if($file->move(public_path().$path,$filename)){
                        FichasFiles::create([
                            "ficha_id"        => $dato->id,
                            "archivo"         => $file->getClientOriginalName(),
                            "url"             => $filename,
                            "ext"             => $file->getClientOriginalExtension()
                        ]);
                    }
                }
            }
        }
        return $dato;
    }
    //api:post/eliminarFicha
    public function eliminarFicha(Request $request){
        DB::DELETE("DELETE FROM fichas WHERE id = '$request->id'");
        //delete files
        $query = DB::SELECT("SELECT * FROM fichas_files WHERE ficha_id = '$request->id'");
        foreach($query as $key => $item){
            $this->deleteFileFicha($item->id);
        }
        return "se elimino correctamente";
    }
    public function deleteFileFicha($id){
        $dato = FichasFiles::find($id);
        $file = $dato->url;
        $ruta_tmp= 'archivos/fichas/';
        $path = $ruta_tmp.$file;
        if(file_exists($path)) {
            if(file_exists($path) ){
                unlink($path);
            }
        }
        $dato->delete();
        return $dato;
    }
    //METODOS PARA VERIFICACIONES
    public function saveFileVerificacion(Request $request)
    {
        $fechaActual = now(); // Usa Carbon para manejar fechas
        $respuesta = false;

        // Si no hay cambios, devuelve una respuesta de éxito directamente
        if ($request->NoCambios) {
            return ["status" => "1", "message" => "Se guardó correctamente"];
        }

        // Definir ruta base para los archivos
        $rutaBase = "archivos/verificaciones/" . $request->tipoDocumento . "/"; // Usar ruta relativa

        // Verificar si la carpeta existe, si no, crearla
        if (!file_exists(public_path($rutaBase))) {
            mkdir(public_path($rutaBase), 0777, true);
        }

        DB::beginTransaction(); // Iniciar transacción
        try {
            // 1️⃣ 🔴 **Eliminar archivos indicados en remove_files**
            if (!empty($request->remove_files)) {
                foreach ($request->remove_files as $fileId) {
                    $archivo = DB::table('evidencia_global_files')->where('egf_id', $fileId)->first();

                    if ($archivo) {
                        $rutaArchivo = public_path($archivo->egf_url . '/' . $archivo->egf_archivo);
                        if (file_exists($rutaArchivo)) {
                            unlink($rutaArchivo);
                        }

                        // Eliminar de la base de datos
                        DB::table('evidencia_global_files')->where('egf_id', $fileId)->delete();
                    }
                }
            }

            // 2️⃣ 🟢 **Guardar los nuevos archivos**
            if ($request->hasFile('archivos')) {
                foreach ($request->file('archivos') as $file) {
                    $filename = uniqid() . "_" . $file->getClientOriginalName();
                    $fileSize = $file->getSize() ? round($file->getSize() / (1024 * 1024), 2) : 0; // Tamaño en MB, si no hay se guarda 0

                    // Buscar si el archivo ya existe en la tabla evidencia_global_files
                    $archivoExistente = DB::table('evidencia_global_files')
                        ->where('egf_referencia', $request->verificacion_id)
                        ->where('egf_archivo', $file->getClientOriginalName())
                        ->first();

                    if ($archivoExistente) {
                        // Si existe, eliminar el archivo viejo y actualizar el registro
                        $oldPath = public_path($archivoExistente->egf_url . '/' . $archivoExistente->egf_archivo);
                        if (file_exists($oldPath)) {
                            unlink($oldPath);
                        }

                        // Guardar el nuevo archivo en la misma carpeta
                        $file->move(public_path($rutaBase), $filename);

                        // Actualizar el registro en la BD
                        DB::table('evidencia_global_files')
                            ->where('egf_id', $archivoExistente->egf_id)
                            ->update([
                                'egf_archivo' => $filename,
                                'egf_url' => $rutaBase,  // Guardar ruta relativa
                                'egf_tamano' => $fileSize,
                                'updated_at' => $fechaActual,
                            ]);
                    } else {
                        // Si el archivo no existe, lo insertamos como nuevo
                        $file->move(public_path($rutaBase), $filename);

                        DB::table('evidencia_global_files')->insert([
                            'egft_id' => $request->tipoDocumento == 'evidencia' ? 3 : 4, // Tipo de archivo
                            'egf_archivo' => $filename,
                            'egf_url' => $rutaBase,  // Guardar ruta relativa
                            'egf_tamano' => $fileSize,
                            'egf_referencia' => $request->verificacion_id,
                            'created_at' => $fechaActual,
                            'updated_at' => $fechaActual,
                            'user_created' => $request->user_created, // Se recibe del request
                        ]);
                    }
                }
            }

            // 3️⃣ 🟠 **Si es factura, simplemente responde sin actualizar la trazabilidad**
            if ($request->tipoDocumento == "factura") {
                DB::commit(); // Confirmar transacción antes de salir
                return ["status" => "1", "message" => "Se guardó correctamente"];
            }

            // 4️⃣ 🔵 **Actualizar trazabilidad en temporadas_verificacion_historico**
            DB::update("UPDATE temporadas_verificacion_historico
                SET estado = '3', fecha_subir_evidencia = ? , id_verificacion = ?
                WHERE contrato = ? AND estado = '2' ", [$fechaActual, $request->contrato,$request->verificacion_id]);


            $respuesta =  DB::UPDATE("UPDATE verificaciones SET `$request->campo_fecha` = '$fechaActual' WHERE `id` = '$request->verificacion_id' ");

            DB::commit(); // Confirmar cambios en la base de datos

            // Retornar el estado de la operación
            return ["status" => "1", "message" => "Se guardó correctamente"];
        } catch (\Exception $e) {
            DB::rollBack(); // Revertir cambios en caso de error
            return ["status" => "0", "message" => "Error: " . $e->getMessage(), "line" => $e->getLine()];
        }
    }





    //FIN METODOS PARA VERIFICACIONES
    //===METODOS NEET===========================
    //METODOS DE FICHAS
    public function saveNeetDocumentos(Request $request){
        if ($request->id > 0) {
            $dato = NeetUpload::find($request->id);
        }else{
            $dato = new NeetUpload();
        }
        $dato->nombre           = $request->nombre;
        $dato->descripcion      = $request->descripcion == null || $request->descripcion == "null" ? null : $request->descripcion;
        $dato->estado           = $request->estado;
        $dato->tema_id          = $request->tema_id;
        $dato->user_created     = $request->user_created;
        $dato->periodo_id       = $request->periodo_id;
        if($request->tipo == 1)  $dato->nee_subnivel = 5;
        else                     $dato->nee_subnivel = $request->nee_subnivel;
        $dato->tipo             = $request->tipo;
        $dato->solucionario     = $request->solucionario;
        $dato->save();
        //si no envia files
        if($request->noSendFiles){
        }
        //si envia files
        else{
            //guardar los files
            if($request->file('archivos')){
                $files = $request->file('archivos');
                foreach($files as $clave => $file){
                    $path = "/archivos/neetFiles/";
                    $filename = uniqid().$file->getClientOriginalName();
                    if($file->move(public_path().$path,$filename)){
                        NeetUploadFiles::create([
                            "neet_upload_id"  => $dato->id,
                            "archivo"         => $file->getClientOriginalName(),
                            "url"             => $filename,
                            "ext"             => $file->getClientOriginalExtension()
                        ]);
                    }
                }
            }
        }
    }
    //api:post/eliminarNEET
    public function eliminarNEET(Request $request){
        DB::DELETE("DELETE FROM neet_upload WHERE id = '$request->id'");
        //delete files
        $query = DB::SELECT("SELECT * FROM neet_upload_files WHERE neet_upload_id = '$request->id'");
        foreach($query as $key => $item){
            $this->deleteFileNEET($item->id);
        }
        return "se elimino correctamente";
    }
    public function deleteFileNEET($id){
        $dato = NeetUploadFiles::find($id);
        $file = $dato->url;
        $ruta_tmp= 'archivos/neetFiles/';
        $path = $ruta_tmp.$file;
        if(file_exists($path)) {
            if(file_exists($path) ){
                unlink($path);
            }
        }
        $dato->delete();
        return $dato;
    }
    //api:post/guardarTareaNeet
    public function guardarTareaNeet(Request $request)
    {
        // VALIDACIÓN
        $validator = Validator::make($request->all(), [
            'neet_cursos_has_material_id' => 'required|integer|exists:neet_cursos_has_material,id',
            'idusuario'                   => 'required|integer|exists:usuario,idusuario',
            'curso'                       => 'required|string|max:50',
            'periodo_id'                  => 'required|integer|exists:periodoescolar,idperiodoescolar',
            'mensaje'                     => 'required|string',
            'archivos.*'                  => 'file|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,zip,rar|max:20480', // max 20MB por archivo
        ]);

        if ($validator->fails()) {
            return [
                "status" => "0",
                "message" => "Errores de validación",
                "errors" => $validator->errors()
            ];
        }

        DB::beginTransaction();

        try {
            if ($request->id > 0) {
                $tarea = NeetTareas::find($request->id);

                if (!$tarea) {
                    return ["status" => "0", "message" => "Tarea no encontrada"];
                }

                if ($tarea->estado == "1") {
                    return ["status" => "0", "message" => "La tarea ya fue calificada"];
                }
            } else {
                $tarea = new NeetTareas();
            }

            $tarea->neet_cursos_has_material_id = $request->neet_cursos_has_material_id;
            $tarea->estudiante_id               = $request->idusuario;
            $tarea->curso                       = $request->curso;
            $tarea->periodo_id                  = $request->periodo_id;
            $tarea->save();

            // GUARDAR LA RESPUESTA
            $respuesta = new NeetTareasRespuesta();
            $respuesta->mensaje         = $request->mensaje;
            $respuesta->idusuario       = $request->idusuario;
            $respuesta->neet_tareas_id  = $tarea->id;
            $respuesta->save();

            // GUARDAR ARCHIVOS
            if ($request->hasFile('archivos')) {
                $files = $request->file('archivos');

                foreach ($files as $file) {
                    $path = "/archivos/neetFiles/tareas/";
                    $filename = uniqid() . '_' . $file->getClientOriginalName();

                    if ($file->move(public_path() . $path, $filename)) {
                        NeetTareasFiles::create([
                            "neet_tareas_respuestas_id" => $respuesta->id,
                            "nombre"                    => $file->getClientOriginalName(),
                            "archivo"                   => $filename,
                            "ext"                       => $file->getClientOriginalExtension()
                        ]);
                    }
                }
            }

            DB::commit();
            return ["status" => "1", "message" => "Se guardó correctamente", "tarea" => $tarea];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                "status" => "0",
                "message" => "Error: " . $e->getMessage(),
                "line" => $e->getLine()
            ];
        }
    }
    //api:post/eliminarRespuestaNeet
    public function eliminarRespuestaNeet(Request $request)
    {
        DB::beginTransaction();

        try {
            $tarea_id  = $request->tarea_id;
            $tarea = NeetTareas::find($tarea_id);
            $estadoTarea = $tarea->estado;
            if($estadoTarea == "1"){
                return ["status" => "0", "message" => "La tarea ya fue calificada"];
            }
            // Validar que exista la respuesta
            $respuesta = NeetTareasRespuesta::find($request->id);

            if (!$respuesta) {
                return ["status" => "0", "message" => "Respuesta no encontrada"];
            }

            // Obtener archivos relacionados
            $archivos = NeetTareasFiles::where('neet_tareas_respuestas_id', $respuesta->id)->get();

            foreach ($archivos as $archivo) {
                $rutaArchivo = public_path('/archivos/neetFiles/tareas/') . $archivo->archivo;

                // Eliminar archivo del servidor si existe
                if (File::exists($rutaArchivo)) {
                    File::delete($rutaArchivo);
                }

                // Eliminar registro de la base de datos
                $archivo->delete();
            }

            // Eliminar la respuesta
            $respuesta->delete();

            DB::commit();
            return ["status" => "1", "message" => "Respuesta y archivos eliminados correctamente"];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                "status" => "0",
                "message" => "Error: " . $e->getMessage(),
                "line" => $e->getLine()
            ];
        }
    }
    //===FIN METODOS NEET========================

    //===METODOS DE REVISION=========================
    public function saveRevision(Request $request) {
        $fechaActual = now();

        // Si no hay cambios, solo actualizamos el estado y salimos
        if ($request->NoCambios) {
            DB::table('verificaciones')
                ->where('id', $request->verificacion_id)
                ->update(['estado_revision' => '1']);
            return ["status" => "1", "message" => "Se guardó correctamente"];
        }

        // Verificar si hay archivos en la solicitud
        $archivosNuevos = false;
        $archivosEliminados = false;

        if ($request->hasFile('archivos')) {
            $files = $request->file('archivos');

            foreach ($files as $file) {
                $path = "archivos/verificaciones/" . $request->tipoDocumento . "/";
                $filename = uniqid() . "_" . $file->getClientOriginalName();
                $fileSize = round($file->getSize() / (1024 * 1024), 2); // Tamaño en MB

                // Buscar si el archivo ya existe para esta verificación
                $archivoExistente = DB::table('evidencia_global_files')
                    ->where('egf_referencia', $request->verificacion_id) //referencia de la verificacion
                    ->where('egf_archivo', $file->getClientOriginalName()) // Comparar solo el nombre
                    ->first();

                if ($archivoExistente) {
                    // Si existe, eliminamos el archivo anterior y actualizamos el registro
                    $oldPath = public_path($archivoExistente->egf_url . '/' . $archivoExistente->egf_archivo);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }

                    // Guardar el nuevo archivo en el mismo lugar
                    $file->move(public_path($path), $filename);

                    // Actualizar el registro en la base de datos
                    DB::table('evidencia_global_files')
                        ->where('egf_id', $archivoExistente->egf_id)
                        ->update([
                            'egf_archivo' => $filename, // Nombre del archivo
                            'egf_url' => $path,  // Guardar ruta relativa
                            'egf_tamano' => $fileSize, // Tamaño en MB
                            'updated_at' => $fechaActual, // Fecha de actualización
                        ]);
                } else {
                    // Si no existe, lo insertamos como nuevo
                    $file->move(public_path($path), $filename);

                    DB::table('evidencia_global_files')->insert([
                        'egft_id' => 2, // Tipo de archivo
                        'egf_archivo' => $filename, // Nombre del archivo
                        'egf_url' => $path,  // Guardar ruta relativa
                        'egf_tamano' => $fileSize, // Tamaño en MB
                        'egf_referencia' => $request->verificacion_id, // Relación con la verificación
                        'created_at' => $fechaActual, // Fecha de creación
                        'updated_at' => $fechaActual, // Fecha de actualización
                        'user_created' => $request->user_created, // Si hay autenticación
                    ]);
                }

                $archivosNuevos = true;  // Hay al menos un archivo nuevo
            }
        }

        // Eliminar los archivos antiguos que ya no están en la nueva solicitud
        if ($request->has('remove_files')) {
            $filesToRemove = $request->remove_files;

            foreach ($filesToRemove as $fileId) {
                // Buscar el archivo en la base de datos
                $archivo = DB::table('evidencia_global_files')->where('egf_id', $fileId)->first();
                if ($archivo) {
                    $oldPath = public_path($archivo->egf_url . '/' . $archivo->egf_archivo);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);  // Eliminar el archivo
                    }

                    // Eliminar el archivo de la base de datos
                    DB::table('evidencia_global_files')->where('egf_id', $fileId)->delete();
                }
            }
            $archivosEliminados = true;  // Se eliminaron archivos
        }

        // Verificar si ya no quedan archivos ni nuevos ni viejos
        $evidenciasExistentes = DB::table('evidencia_global_files')
        ->where('egf_referencia', $request->verificacion_id)
        ->exists(); // Verifica si existe al menos una evidencia para la verificación

        if (!$archivosNuevos && !$request->has('remove_files') && !$evidenciasExistentes) {
        DB::table('verificaciones')
            ->where('id', $request->verificacion_id)
            ->update(['estado_revision' => '0']);  // Restablecer estado a 0 si no hay archivos ni evidencias
        } else {
        // Si hay archivos nuevos o eliminados, o si ya hay evidencias, mantener el estado de revisión como 1
        DB::table('verificaciones')
            ->where('id', $request->verificacion_id)
            ->update(['estado_revision' => '1']);
        }

        return ["status" => "1", "message" => "Se guardó correctamente"];
    }

    public function removeArchivoEvidenciaGlobal(Request $request)
    {
        try {
            $fileId = $request->input('file_id');

            if (!$fileId) {
                return ["status" => "0", "message" => "ID de archivo no proporcionado"];
            }

            // Buscar el archivo en la base de datos
            $archivo = DB::table('evidencia_global_files')->where('egf_id', $fileId)->first();

            if ($archivo) {
                $oldPath = public_path($archivo->egf_url . '/' . $archivo->egf_archivo);

                if (file_exists($oldPath)) {
                    unlink($oldPath); // Eliminar el archivo físico
                }

                // Eliminar el registro de la base de datos
                DB::table('evidencia_global_files')->where('egf_id', $fileId)->delete();

                return ["status" => "1", "message" => "Archivo eliminado correctamente"];
            } else {
                return ["status" => "0", "message" => "Archivo no encontrado"];
            }
        } catch (\Exception $e) {
            return [
                "status" => "0",
                "message" => "Error al eliminar el archivo: " . $e->getMessage()
            ];
        }
    }


    //===FIN METODOS DE REVISION========================

    //===METODOS DE COPIAR ARCHIVOS A OTRA CARPETA========================
    //api:get/copiarArchivosPreguntas
    public function copiarArchivosPreguntas(Request $request)
    {
        DB::beginTransaction();
        try {
            // Traer preguntas que vienen de Prolipa y aún no han sido procesadas
            $preguntas = DB::select("SELECT * FROM salle_preguntas p WHERE p.preguntafromProlipa = '1'");

            $rutaFrom = public_path('/archivos/img/img_preguntas');
            $rutaTo = public_path('/archivos/img/salle/img_preguntas');

            // Crear carpeta destino si no existe
            if (!file_exists($rutaTo)) {
                mkdir($rutaTo, 0777, true);
            }

            $noEncontradas = [];
            $copiadas = 0;

            foreach ($preguntas as $item) {
                //  Marcar como procesada apenas entra al foreach
                DB::table('salle_preguntas')
                    ->where('id_pregunta', $item->id_pregunta)
                    ->update(['preguntafromProlipa' => 2]);

                $file = $item->img_pregunta;
                if (!$file) continue;

                $rutaArchivoOrigen = $rutaFrom . '/' . $file;
                $rutaArchivoDestino = $rutaTo . '/' . $file;

                if (file_exists($rutaArchivoOrigen)) {
                    copy($rutaArchivoOrigen, $rutaArchivoDestino);
                    $copiadas++;
                } else {
                    $noEncontradas[] = $file;
                }
            }

            // Procesar opciones de cada pregunta
            foreach ($preguntas as $item) {
                $this->copiarOpcionesPreguntas($item->id_pregunta);
            }

            DB::commit();

            return [
                "status" => "1",
                "message" => "Proceso completado correctamente.",
                "archivos_copiados" => $copiadas,
                "no_encontradas" => $noEncontradas
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                "status" => "0",
                "message" => "Error al copiar archivos: " . $e->getMessage()
            ];
        }
    }

    public function copiarOpcionesPreguntas($id_pregunta)
    {
        try {
            $rutaFrom = public_path('/archivos/img/img_preguntas');
            $rutaTo = public_path('/archivos/img/salle/img_preguntas');
            $copiadas = 0;
            $noEncontradas = [];

            $opciones = DB::select("SELECT * FROM salle_opciones_preguntas WHERE id_pregunta = ?", [$id_pregunta]);

            foreach ($opciones as $item) {
                $file = $item->img_opcion;
                if (!$file) continue;

                $rutaArchivoOrigen = $rutaFrom . '/' . $file;
                $rutaArchivoDestino = $rutaTo . '/' . $file;

                if (file_exists($rutaArchivoOrigen)) {
                    copy($rutaArchivoOrigen, $rutaArchivoDestino);
                    $copiadas++;
                } else {
                    $noEncontradas[] = $file;
                }
            }

            return [
                "status" => "1",
                "message" => "Opciones copiadas correctamente.",
                "archivos_copiados" => $copiadas,
                "no_encontradas" => $noEncontradas
            ];

        } catch (\Exception $e) {
            throw new \Exception("Error al copiar opciones: " . $e->getMessage());
        }
    }

    public function verificarContenidoDigital(Request $request)
    {
        try {
            $carpeta = $request->input('libro');

            if (!$carpeta) {
                return response()->json([
                    'existe' => false,
                    'message' => 'No se ha especificado la carpeta del contenido digital.',
                ], 200);
            }

            $ruta = public_path("archivos/upload/libro/$carpeta/index.php");

            if (File::exists($ruta)) {
                return response()->json([
                    'existe' => true,
                    'message' => 'El contenido digital está disponible.',
                ], 200);
            } else {
                return response()->json([
                    'existe' => false,
                    'message' => "No se encontró el archivo 'index.php' en la carpeta [{$carpeta}]. Verifique que la carpeta del contenido digital esté correctamente registrado.",
                ], 200);
            }

        } catch (\Throwable $e) {
            return response()->json([
                'existe' => false,
                'message' => 'Error interno al verificar el contenido digital.',
                'error' => $e->getMessage(), // opcional: puedes quitarlo en producción
            ], 500);
        }
    }

    public function verificarPortadaContenido(Request $request)
    {
        try {
            $carpeta = $request->input('carpeta');
            $portada = $request->input('portada');

            if (!$carpeta || trim($carpeta) === '' || strtolower($carpeta) === 'sin registro') {
                return response()->json([
                    'tiene_portada' => false,
                    'mensaje' => 'Carpeta no especificada o sin registro',
                    'tipo' => 'no_carpeta',
                ], 200);
            }
            if (!$portada || trim($portada) === '' || strtolower($portada) === 'sin registro') {
                return response()->json([
                    'tiene_portada' => false,
                    'mensaje' => 'Portada no especificada o sin registro',
                    'tipo' => 'no_portada',
                ], 200);
            }
            $ruta = public_path("archivos/upload/libro/{$carpeta}/{$portada}");

            if (File::exists($ruta)) {
                return response()->json([
                    'tiene_portada' => true,
                    'mensaje' => 'Portada encontrada',
                    'tipo' => 'existe',
                ], 200);
            } else {
                return response()->json([
                    'tiene_portada' => false,
                    'mensaje' => 'Carpeta existe pero portada no encontrada',
                    'tipo' => 'carpeta_sin_portada',
                ], 200);
            }
        } catch (\Exception $e) {
            return response()->json([
                'tiene_portada' => false,
                'mensaje' => 'Error interno al verificar portada: ' . $e->getMessage(),
                'tipo' => 'error',
            ], 500);
        }
    }

    public function verificarPortadaContenido_Masivo(Request $request)
    {
        try {
            $items = $request->input('items', []); // Esperamos: [{carpeta: "...", portada: "..."}, ...]

            if (!is_array($items) || empty($items)) {
                return response()->json([
                    'resultados' => [],
                    'mensaje' => 'No se enviaron items para verificar'
                ], 200);
            }

            $resultados = [];

            foreach ($items as $index => $item) {
                $carpeta = trim($item['carpeta'] ?? '');
                $portada = trim($item['portada'] ?? '');

                $key = $carpeta . '|' . $portada; // clave única para identificar

                // Validaciones rápidas
                if (empty($carpeta) || strtolower($carpeta) === 'sin registro') {
                    $resultados[$key] = [
                        'tiene_portada' => false,
                        'mensaje' => 'Carpeta no especificada o sin registro',
                        'tipo' => 'no_carpeta',
                    ];
                    continue;
                }

                if (empty($portada) || strtolower($portada) === 'sin registro') {
                    $resultados[$key] = [
                        'tiene_portada' => false,
                        'mensaje' => 'Portada no especificada o sin registro',
                        'tipo' => 'no_portada',
                    ];
                    continue;
                }

                $ruta = public_path("archivos/upload/libro/{$carpeta}/{$portada}");

                if (File::exists($ruta)) {
                    $resultados[$key] = [
                        'tiene_portada' => true,
                        'mensaje' => 'Portada encontrada',
                        'tipo' => 'existe',
                    ];
                } else {
                    // Verificamos si al menos la carpeta existe
                    $rutaCarpeta = public_path("archivos/upload/libro/{$carpeta}");
                    $tipo = File::isDirectory($rutaCarpeta) ? 'carpeta_sin_portada' : 'no_carpeta';

                    $resultados[$key] = [
                        'tiene_portada' => false,
                        'mensaje' => $tipo === 'carpeta_sin_portada'
                            ? 'Carpeta existe pero portada no encontrada'
                            : 'La carpeta no existe',
                        'tipo' => $tipo,
                    ];
                }
            }

            return response()->json([
                'resultados' => $resultados,
                'total' => count($resultados),
                'mensaje' => 'Verificación masiva completada'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'resultados' => [],
                'mensaje' => 'Error interno: ' . $e->getMessage(),
                'total' => 0
            ], 500);
        }
    }

    public function verificarContenidoPdf(Request $request)
    {
        $archivo = $request->input('archivo');
        $tipo = $request->input('tipo');

        $carpetas = [
            'pdf_con_guia' => 'pdfconguia',
            'pdf_sin_guia' => 'pdfsinguia',
            'pdf_guiadidactica' => 'pdfguiadidactica',
        ];

        if (!$archivo || !$tipo || !isset($carpetas[$tipo])) {
            return response()->json([
                'existe' => false,
                'mensaje' => 'Parámetros incompletos o tipo inválido.',
            ], 400);
        }

        $ruta = public_path("archivos/upload/{$carpetas[$tipo]}/{$archivo}");

        if (File::exists($ruta)) {
            return response()->json([
                'existe' => true,
                'mensaje' => 'El contenido digital está disponible.',
            ]);
        } else {
            return response()->json([
                'existe' => false,
                'mensaje' => "Archivo '{$archivo}' no encontrado.",
            ]);
        }
    }
    //===FIN METODOS DE COPIAR ARCHIVOS A OTRA CARPETA========================
    // public function removerArchivosTareas(Request $request)
    // {
    //     $ruta = $request->ruta;
    //     $cursos = json_decode($request->cursos, true); // Recuperar el array enviado
    //     $resultados = [];

    //     foreach ($cursos as $curso) {
    //         $idCurso = $curso['curso_idcurso'];
    //         $codigo  = $curso['codigo'];

    //         // Buscar tareas y archivos asociados al curso
    //         $tareas = DB::table('usuario_tarea as t')
    //             ->leftJoin('curso as c', 'c.idcurso', '=', 't.curso_idcurso')
    //             ->where('t.curso_idcurso', $idCurso)
    //             ->get();

    //         $eliminados = 0;
    //         $noEncontrados = 0;

    //         foreach ($tareas as $tarea) {
    //             // Validar si existe el archivo (propiedad url)
    //             if ($tarea->url) {
    //                 // Eliminar el archivo físico si existe
    //                 $filePath = public_path($ruta . '/' . $tarea->url);
    //                 if (file_exists($filePath)) {
    //                     unlink($filePath);
    //                     $eliminados++;
    //                 } else {
    //                     $noEncontrados++;
    //                 }
    //                 // Eliminar el registro de la base de datos si lo deseas
    //                 DB::table('usuario_tarea')->where('id', $tarea->id)->update(['fecha_quitado_archivos' => now()]);
    //             } else {
    //                 $noEncontrados++;
    //             }
    //         }

    //         // Marcar el curso con la fecha actual
    //         DB::table('curso')
    //             ->where('idcurso', $idCurso)
    //             ->update(['fecha_quitado_archivos' => now(), 'usuario_quita_archivos' => $request->usuario_quita_archivos]);

    //         // Guardar el resultado para este curso
    //         $resultados[] = [
    //             'codigo' => $codigo,
    //             'idcurso' => $idCurso,
    //             'eliminados' => $eliminados,
    //             'no_encontrados' => $noEncontrados,
    //         ];
    //     }

    //     return response()->json([
    //         'status' => 1,
    //         'resultados' => $resultados,
    //         'message' => 'Proceso completado'
    //     ]);
    // }

    //API:POST/removerArchivosTareas
    //API:POST/removerArchivosTareas
    public function removerArchivosTareas(Request $request){
        $ruta      = $request->ruta;
        $ids       = json_decode($request->ids, true);
        $usuarioId = $request->usuario_quita_archivos;

        if (empty($ids)) {
            return response()->json(['status' => 0, 'message' => 'No se enviaron IDs.']);
        }

        $tareas = DB::table('usuario_tarea as t')
            ->leftJoin('curso as c', 'c.idcurso', '=', 't.curso_idcurso')
            ->whereIn('t.id', $ids)
            ->whereNull('t.fecha_quitado_archivos')
            ->select('t.id', 't.url', 't.curso_idcurso', 'c.codigo')
            ->get();

        $eliminados      = 0;
        $noEncontrados   = 0;
        $resultados      = [];
        $cursosAfectados = [];

        foreach ($tareas as $tarea) {
            $estado = 'no_encontrado';

            if ($tarea->url) {
                $filePath = public_path($ruta . '/' . $tarea->url);
                if (file_exists($filePath)) {
                    unlink($filePath);
                    $eliminados++;
                    $estado = 'eliminado';
                } else {
                    $noEncontrados++;
                    DB::table('usuario_tarea')
                        ->where('id', $tarea->id)
                        ->update(['archivo_no_encontrato_files' => 1]);
                }
            } else {
                $noEncontrados++;
            }

            DB::table('usuario_tarea')
                ->where('id', $tarea->id)
                ->update(['fecha_quitado_archivos' => now()]);

            $cursosAfectados[$tarea->curso_idcurso] = $tarea->codigo;

            $resultados[] = [
                'id'     => $tarea->id,
                'url'    => $tarea->url,
                'codigo' => $tarea->codigo,
                'estado' => $estado,
            ];
        }

        foreach ($cursosAfectados as $idCurso => $codigo) {
            DB::table('curso')
                ->where('idcurso', $idCurso)
                ->update([
                    'fecha_quitado_archivos' => now(),
                    'usuario_quita_archivos' => $usuarioId,
                ]);
        }

        return response()->json([
            'status'         => 1,
            'eliminados'     => $eliminados,
            'no_encontrados' => $noEncontrados,
            'resultados'     => $resultados,
            'message'        => 'Proceso completado',
        ]);
    }

    public function getFileSize(Request $request)
    {
        $ruta           = $request->ruta;
        $archivosTareas = json_decode($request->archivosTareas, true); // Recuperar el array enviado

        foreach ($archivosTareas as &$archivo) {
            $fileName = $archivo['url'];
            $path = public_path($ruta . '/' . $fileName);

            if (file_exists($path)) {
                $size = filesize($path); // bytes
                $archivo['peso'] = $this->formatBytes($size);
            } else {
                $archivo['peso'] = null;
            }
        }

        // Devolver todo el array con los pesos agregados
        return response()->json($archivosTareas);
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Copia una imagen de juego en el servidor de archivos.
     * Recibe: nombre_imagen, id_tipo_juego
     * Tipos con imágenes: 3 y 4 → seleccionSimple | 6 → rompecabezas
     * Devuelve: nuevo_nombre (nombre del archivo copiado) o error
     */
    public function copiarImagenJuego(Request $request)
    {
        $nombreOrigen  = $request->input('nombre_imagen');
        $id_tipo_juego = (int) $request->input('id_tipo_juego', 0);

        if (empty($nombreOrigen)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El campo nombre_imagen es requerido'
            ], 422);
        }

        // Determinar carpeta según tipo de juego
        if (in_array($id_tipo_juego, [3, 4])) {
            $subcarpeta = 'seleccionSimple';
        } elseif ($id_tipo_juego === 6) {
            $subcarpeta = 'rompecabezas';
        } else {
            // Tipos 1, 2 y cualquier otro no manejan imágenes
            return response()->json([
                'status'  => 'error',
                'message' => "El tipo de juego {$id_tipo_juego} no maneja imágenes"
            ], 422);
        }

        $carpeta    = public_path("/archivos/images/imagenes_juegos/{$subcarpeta}/");
        $rutaOrigen = $carpeta . $nombreOrigen;

        if (!file_exists($rutaOrigen)) {
            return response()->json([
                'status'  => 'error',
                'message' => "Imagen no encontrada: {$nombreOrigen}"
            ], 404);
        }

        $extension   = pathinfo($nombreOrigen, PATHINFO_EXTENSION);
        $nuevoNombre = uniqid() . '.' . $extension;
        $rutaDestino = $carpeta . $nuevoNombre;

        if (!copy($rutaOrigen, $rutaDestino)) {
            return response()->json([
                'status'  => 'error',
                'message' => "No se pudo copiar la imagen: {$nombreOrigen}"
            ], 500);
        }

        return response()->json([
            'status'       => 1,
            'nuevo_nombre' => $nuevoNombre,
        ]);
    }

    /**
     * API genérica para actualizar archivos de usuarios
     * Recibe: idusuario, campo (nombre del campo a actualizar), archivo
     */
    public function actualizarArchivosUsuario(Request $request)
    {        try {
            // Validación de parámetros básicos
            $request->validate([
                'idusuario' => 'required|integer',
                'campo' => 'required|string',
                'archivo' => 'required|file|image|max:3072', // Máximo 3MB
            ], [
                'idusuario.required' => 'El ID de usuario es obligatorio.',
                'campo.required' => 'El campo a actualizar es obligatorio.',
                'archivo.required' => 'Debe seleccionar un archivo.',
                'archivo.image' => 'El archivo debe ser una imagen.',
                'archivo.max' => 'La imagen no puede ser mayor a 3MB.',
            ]);

            $file = $request->file('archivo');
            $campo = $request->campo;
            $idusuario = $request->idusuario;

            // Definir la ruta según el campo
            $carpetasPorCampo = [
                'firma' => '/archivos/usuarios/firmas/',
                // Agregar más campos aquí en el futuro
            ];

            // Validar que el campo sea válido
            if (!isset($carpetasPorCampo[$campo])) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Campo no válido para actualizar archivos.'
                ], 400);
            }

            $ruta = public_path($carpetasPorCampo[$campo]);

            // Crear carpeta si no existe
            if (!file_exists($ruta)) {
                mkdir($ruta, 0755, true);
            }

            // Generar nombre de archivo único basado en el campo y el ID de usuario
            $nombreArchivo = $campo . '_' . $idusuario . '.' . $file->getClientOriginalExtension();

            // Eliminar archivo anterior si existe
            $archivoAnterior = $ruta . $nombreArchivo;
            if (file_exists($archivoAnterior)) {
                unlink($archivoAnterior);
            }

            // Mover el archivo nuevo
            $file->move($ruta, $nombreArchivo);

            // Actualizar en la base de datos
            DB::update("UPDATE usuario SET `$campo` = ? WHERE idusuario = ?", [$nombreArchivo, $idusuario]);

            return response()->json([
                'status' => 1,
                'message' => 'Archivo guardado correctamente.',
                'archivo' => $nombreArchivo
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error al guardar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }
}
