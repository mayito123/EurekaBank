<?php 
require_once "../modelos/cuenta.php";
require_once "../modelos/clientes.php";
require_once "../modelos/movimientos.php";

		

// verificar que se este logeado
if (strlen(session_id())<1) 
	session_start();

$cuenta = new cuenta();

$cliente = new cliente();



$movimientos = new movimientos();





switch ($_GET["op"]) {
	case 'guardaryeditar':
	if (empty($idventa)) {
		$rspta=$cuenta->insertar($idcliente,$idusuario,$tipo_comprobante,$serie_comprobante,$num_comprobante,$fecha_hora,$impuesto,$total_venta,$_POST["idarticulo"],$_POST["cantidad"],$_POST["precio_venta"],$_POST["descuento"]); 
		echo $rspta ? "Datos registrados correctamente" : "No se pudo registrar los datos";
	}else{
        
	}
		break;
	

	case 'anular':
		$rspta=$cuenta->anular($idventa);
		echo $rspta ? "Ingreso anulado correctamente" : "No se pudo anular el ingreso";
		break;
	
	case 'mostrar':
		$id_cta= $_POST['id_cta'];
		$rspta=$cuenta->mostrar($id_cta);
		echo json_encode($rspta);
		break;

	case 'crearMovimiento':

		//obtenemos la fecha actual
		$date = date('Y-m-d h:i:s');

		// sacamos los datos del formulario enviado por get
		$id_cta=$_GET['id_cta'];
		$id_tipo_mov	=isset($_POST["id_tipo_mov"])? limpiarCadena($_POST["id_tipo_mov"]):"";
		$importe_mov	=isset($_POST["importe_mov"])? limpiarCadena($_POST["importe_mov"]):"";
		$cuenta_ref_mov =isset($_POST["cuenta_ref"])? limpiarCadena($_POST["cuenta_ref"]):"";

		$rspta = $cuenta->saldo($id_cta);
		$reg=$rspta->fetch_object();
		if($id_tipo_mov=='6' AND $cuenta_ref_mov==''){
			echo 'Cuenta de referencia para la transferencia no suministrada';
		}elseif(($id_tipo_mov==1 OR $id_tipo_mov==3) OR floatval($reg->saldo_cta)>floatval($importe_mov)){
				// verificamos si el usuario logeado es un cliente o un empleado

			if($_SESSION['rol']==3){
				// pasamos el id del empleado internet
				$id_empleado = 9999;
			}elseif($_SESSION['rol']==2){
				// buscamos el id del empleado logueado usando el modelo de empleado
				require_once "../modelos/empleado.php";
				$empleado = new empleado();
				$rspta = $empleado->obtener_id($_SESSION['id_usuario']);

				// verificamos el exito en la consulta
				if($rspta){
					$reg=$rspta->fetch_object();
					$id_empleado=$reg->id_empleado;
				}else{
					echo 'Error al obtener id del usuario empleado';
				}

			}

			// echo $id_cta;

			//realizamos el registro del movimiento
			$rspta = $movimientos->insertar($importe_mov,$cuenta_ref_mov,$date,$id_empleado,$id_cta,$id_tipo_mov);
			echo $rspta;
				
			// verificamos que exista saldo en caso de que el movimiento sea de salida
		}else{
			echo 'Sin saldo para la transaccion';
		}

		
		
	break;
		
	
	case 'anular':
			$rspta=$cuenta->anular($idventa);
			echo $rspta ? "Ingreso anulado correctamente" : "No se pudo anular el ingreso";
	break;
		
	case 'mostrar':
			$id_cta= $_POST['id_cta'];
			$rspta=$cuenta->mostrar($id_cta);
			echo json_encode($rspta);
	break;

	case 'listarMovimientos':
		
		//recibimos el id_cta
		$id_cta=$_GET['id_cta'];

		$movimientos=$movimientos->listarMovimentos($id_cta);
		// echo $rspta;
		while ($reg=$movimientos->fetch_object()) {
			$data[]=array(
			"0"=>$reg->id_mov,
			"1"=>$reg->fecha_creacion_mov,
			"2"=>$reg->nombre_mov,
			"3"=>$reg->accion_tipo_mov,
			"4"=>$reg->importe_mov,
			"5"=>$reg->cuenta_ref_mov,
			"6"=>$reg->id_empleado,
			"7"=>($reg->estado_mov==1)?'<span class="label bg-green">Aceptado</span>'
				:($reg->estado_mov==2?'<span class="label bg-red">Anulado</span>':'<span class="label bg-red">Cancelado</span>')
			);
		}
		$results=array(
             "sEcho"=>1,//info para datatables
             "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
             "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
             "aaData"=>$data); 
		echo json_encode($results);
		
		
	
		break;

    case 'listar':

		

		// en caso de que el cliente sea el que realiza la accion
		if( $_SESSION['rol']==3){
			$rspta = $cliente->obtener_id($_SESSION['id_usuario']);
			$reg=$rspta->fetch_object();
			$id_cliente = $reg->id_cliente;
		}else{
			// obtener el id del cliente enviado por get
			$id_cliente = $_GET["id_cliente"];
			// echo $id_cliente;
		}
		

		$rspta=$cuenta->listar_cuentas_cliente($id_cliente);
		$data=Array();
		// echo $rspta;
		while ($reg=$rspta->fetch_object()) {

			$data[]=array(
            "0"=>(($reg->estado_cta==1)?'<button class="btn btn-warning btn-xs" onclick="mostrar('.$reg->id_cta.')"><i class="fa fa-eye"></i></button>':''),
            "1"=>$reg->id_cta,
            "2"=>$reg->saldo_cta,
            "3"=>$reg->desc_mon,
            "4"=>$reg->fecha_creacion_cta,
            "5"=>$reg->num_mov_cuenta,
            "6"=>($reg->estado_cta==1)?'<span class="label bg-green">Activa</span>':'<span class="label bg-red">Inactiva</span>'
            );
		}
		$results=array(
             "sEcho"=>1,//info para datatables
             "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
             "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
             "aaData"=>$data); 
		// echo $results;
		echo json_encode($results);
		break;

		case 'selectTipoMon':
			require_once "../modelos/tipo_mon.php";
			$tipo_mon = new tipoMon();

			$rspta = $tipo_mon->listar();

			$options = '';

			while ($reg = $rspta->fetch_object()) {
				echo '<option value='.$reg->id_mon.'>'.$reg->desc_mon.'</option>';
			}
		break;

		case 'selectTipoMov':
			require_once "../modelos/tipo_mov.php";
			$tipo_mov = new tipoMov();

			$rspta = $tipo_mov->listar();

			$options = '';

			while ($reg = $rspta->fetch_object()) {
				echo '<option value='.$reg->id_tipo_mov.'>'.$reg->nombre_mov.'</option>';
			}
		break;

		case 'listarArticulos':
		require_once "../modelos/Articulo.php";
		$articulo=new Articulo();

				$rspta=$articulo->listarActivosVenta();
		$data=Array();

		while ($reg=$rspta->fetch_object()) {
			$data[]=array(
            "0"=>'<button class="btn btn-warning" onclick="agregarDetalle('.$reg->idarticulo.',\''.$reg->nombre.'\','.$reg->precio_venta.')"><span class="fa fa-plus"></span></button>',
            "1"=>$reg->nombre,
            "2"=>$reg->categoria,
            "3"=>$reg->codigo,
            "4"=>$reg->stock,
            "5"=>$reg->precio_venta,
            "6"=>"<img src='../files/articulos/".$reg->imagen."' height='50px' width='50px'>"
          
              );
		}
		$results=array(
             "sEcho"=>1,//info para datatables
             "iTotalRecords"=>count($data),//enviamos el total de registros al datatable
             "iTotalDisplayRecords"=>count($data),//enviamos el total de registros a visualizar
             "aaData"=>$data); 
		echo json_encode($results);

				break;
}
 ?>