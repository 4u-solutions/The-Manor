<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\adminModels\ProductosModel;
use App\adminModels\pedidosModel;
use App\adminModels\inventarioModel;
use App\adminModels\inventarioIngresosModel;
use App\adminModels\pedidosDetalleModel;
use App\adminModels\UserAdmin;

class bodegaController extends Controller
{
  protected $redirecUlr;
  public function __construct()
  {
      $this->middleware('auth:admin');
      $this->back = 'admin/inventario';
  }

  public function inventario(){
    $edit         = false;
    $action       = 1;

    $fecha = date('Y-m-d');

    $ultima_fecha_invent = @DB::select("select fecha from admin_inventario where fecha < '$fecha' order by fecha desc limit 1;")[0]->fecha;
    $dia_anterior = date('Y-m-d', strtotime("-1 day", strtotime($fecha)));
    $dia_despues  = date('Y-m-d', strtotime("+1 day", strtotime($fecha)));

    if (strtotime(date('H:i:s')) < strtotime(config('global.horario_cierre'))) {
      $fecha_inicio  = date('Y-m-d ' . config('global.horario_apertura'), strtotime($dia_anterior));
    } else {
      $fecha_inicio  = date('Y-m-d ' . config('global.horario_apertura'), strtotime($fecha));
    }
    $fecha_final = date('Y-m-d ' . config('global.horario_cierre'), strtotime($dia_despues));
    $str = "select distinct ap.id, tw.contable, ap.nombre, ap.id_tipo, ap.precio, if (ai.cantidad_final is null, if (ai.cantidad_inicial, ai.cantidad_inicial, 0) + if (ai.recarga, ai.recarga, 0) + if (aii.ingreso is null, 0, aii.ingreso), ai.cantidad_final + if (aii.ingreso is null, 0, aii.ingreso)) inicial, ai2.cantidad_inicial, ai2.recarga, if (producto_vendido.cantidad is null, 0, producto_vendido.cantidad) vendido, ai2.cantidad_final from admin_productos ap left join admin_inventario ai on (ap.id = ai.id_producto and ai.fecha = '$ultima_fecha_invent') left join admin_inventario_ingresos aii on (ap.id = aii.id_producto and aii.fecha between '$dia_anterior' and '$fecha') left join admin_inventario ai2 on (ap.id = ai2.id_producto and ai2.fecha = '$fecha') left join (select id_producto, sum(cantidad) cantidad from admin_pedidos_detalle left join admin_pedidos on (admin_pedidos_detalle.id_pedido = admin_pedidos.id and admin_pedidos.id_tipo <= 5) where aprobado and despachado and admin_pedidos_detalle.estado and admin_pedidos_detalle.created_at between '$fecha_inicio' and '$fecha_final' group by id_producto) producto_vendido on (ap.id = producto_vendido.id_producto) left join admin_tipo_waro tw on (tw.id = ap.id_tipo) where ap.estado order by ap.id_tipo, ap.orden;";
    // echo $str; exit();
    $inventario = DB::select($str);
    
    $data = inventarioModel::where('fecha', $fecha)->get();
    
    if (count($data) > 0) {
      $action = 2;
      $edit = true;
    }
    
    Config::set('extra_button', true);

    return view('admin.bodega.inventario', [
      'menubar'   => $this->list_sidebar(),
      'productos' => $inventario,
      'edit'      => $edit,
      'action'    => $action,
      'fecha'     => date('Y-m-d'),
      'eb_data'   => (object) array(
        array(
          'feather' => 'upload',
          'route'   => 'admin.bodega.traslado',
          'tooltip' => 'Traslado a barra',
          'params'  => ['id_pedido' => 0]
        ),
        array(
          'feather' => 'gift',
          'tooltip' => 'Cortesías',
          'route'   => 'admin.gerencia.agregar_cortesia',
          'params'  => ['id_pedido' => 0]
        ),
        array(
          'feather' => 'plus-circle',
          'tooltip' => 'Venta',
          'route'   => 'admin.bodega.venta_bodega',
          'params'  => []
        ),
        array(
          'feather' => 'dollar-sign',
          'tooltip' => 'Descarga de efectivo',
          'route'   => 'admin.cobrador.descarga_efectivo',
          'params'  => []
        )
      )
    ]);
  }

  public function show($fecha) {
    $edit         = false;
    $action       = 1;

    $fecha = $fecha ?: date('Y-m-d');

    $str = "select fecha from admin_inventario where fecha < '$fecha' order by fecha desc limit 1;";
    $ultima_fecha_invent = @DB::select($str)[0]->fecha;
    $dia_anterior = date('Y-m-d', strtotime("-1 day", strtotime($fecha)));
    $dia_despues  = date('Y-m-d', strtotime("+1 day", strtotime($fecha)));

    if (strtotime(date('H:i:s')) < strtotime(config('global.horario_cierre'))) {
      $fecha_inicio  = date('Y-m-d ' . config('global.horario_apertura'), strtotime($dia_anterior));
    } else {
      $fecha_inicio  = date('Y-m-d ' . config('global.horario_apertura'), strtotime($fecha));
    }
    $fecha_inicio  = date('Y-m-d ' . config('global.horario_apertura'), strtotime($fecha));
    $fecha_final = date('Y-m-d ' . config('global.horario_cierre'), strtotime($dia_despues));
    $str = "select distinct ap.id, tw.contable, ap.nombre, ap.id_tipo, ap.precio, if (ai.cantidad_final is null, if (ai.cantidad_inicial, ai.cantidad_inicial, 0) + if (ai.recarga, ai.recarga, 0) + if (aii.ingreso is null, 0, aii.ingreso), ai.cantidad_final + if (aii.ingreso is null, 0, aii.ingreso)) inicial, ai2.cantidad_inicial, ai2.recarga, if (producto_vendido.cantidad is null, 0, producto_vendido.cantidad) vendido, ai2.cantidad_final from admin_productos ap left join admin_inventario ai on (ap.id = ai.id_producto and ai.fecha = '$ultima_fecha_invent') left join admin_inventario_ingresos aii on (ap.id = aii.id_producto and aii.fecha between '$dia_anterior' and '$fecha') left join admin_inventario ai2 on (ap.id = ai2.id_producto and ai2.fecha = '$fecha') left join (select id_producto, sum(cantidad) cantidad from admin_pedidos_detalle left join admin_pedidos on (admin_pedidos_detalle.id_pedido = admin_pedidos.id and admin_pedidos.id_tipo <= 5) where aprobado and despachado and admin_pedidos_detalle.estado and admin_pedidos_detalle.created_at between '$fecha_inicio' and '$fecha_final' group by id_producto) producto_vendido on (ap.id = producto_vendido.id_producto) left join admin_tipo_waro tw on (tw.id = ap.id_tipo) where ap.estado order by ap.id_tipo, ap.orden;";
    // echo $str; exit();
    $inventario = DB::select($str);

    $data = inventarioModel::where('fecha', $fecha)->get();
    
    if (count($data) > 0) {
      $action = 2;
      $edit = true;
    }
    
    Config::set('extra_button', true);

    return view('admin.bodega.inventario', [
      'menubar'   => $this->list_sidebar(),
      'productos' => $inventario,
      'edit'      => $edit,
      'fecha'     => $fecha,
      'action'    => $action,
      'eb_data'   => (object) array(
        array(
          'feather' => 'upload',
          'route'   => 'admin.bodega.traslado',
          'tooltip' => 'Traslado a barra',
          'params'  => ['id_pedido' => 0]
        ),
        array(
          'feather' => 'gift',
          'tooltip' => 'Cortesías',
          'route'   => 'admin.gerencia.agregar_cortesia',
          'params'  => ['id_pedido' => 0]
        ),
        array(
          'feather' => 'plus-circle',
          'tooltip' => 'Venta',
          'route'   => 'admin.bodega.venta_bodega',
          'params'  => []
        ),
        array(
          'feather' => 'dollar-sign',
          'tooltip' => 'Descarga de efectivo',
          'route'   => 'admin.cobrador.descarga_efectivo',
          'params'  => []
        )
      )
    ]);
  }

  public function store(Request $request) {
    foreach($_POST['inventario_inicial'] as $key => $item) {
      $model = inventarioModel::create([
        'id_producto'      => $key,
        'cantidad_inicial' => $_POST['inventario_inicial'][$key][0] ?: 0,
        'cantidad_final'   => $_POST['inventario_final'][$key][0] ?: 0,
        'fecha'            => $_POST['fecha']
      ]);
      $model->timestamps = false;
      $model->save();
    }

    return redirect($this->back . '/' . $_POST['fecha'])->with('success','Guardado correctamente!');
  }

  public function actualizar_inventario(Request $request) {
    foreach($_POST['inventario_inicial'] as $key => $item) {
      DB::table('admin_inventario')->where('fecha', $_POST['fecha'])->where('id_producto', $key)->update([
        'id_producto'      => $key,
        'cantidad_inicial' => $_POST['inventario_inicial'][$key][0] ?: 0,
        'cantidad_final'   => $_POST['inventario_final'][$key][0] ?: 0,
        'fecha'            => $_POST['fecha']
      ]);
    }

    return redirect($this->back . '/' . $_POST['fecha'])->with('success','Guardado correctamente!');
  }

  public function destroy($id) {
    $model = new _Model;
    $model::find($id)->delete();

    return redirect()->back()->with('success','Borrado correctamente!');
  }

  public function pedidos(){
    $str = "select ap.id, ap.id_tipo, atp.nombre tipo, au.name mesero, ap.cliente, atpe.nombre estado, atpe.color, ap.id_estado, ap.monto from admin_pedidos ap left join admin_tipo_pedido atp on (ap.id_tipo = atp.id) left join admin_users au on (ap.id_usuario = au.id) left join admin_tipo_pedido_estado atpe on (ap.id_estado = atpe.id) where ap.created_at >= (NOW() - INTERVAL 24 HOUR) and ap.id_estado = 4 and ap.estado order by ap.id_estado desc;";
    $data = DB::select($str);
    
    Config::set('extra_button', true);

    return view('admin.bodega.pedidos', [
      'menubar' => $this->list_sidebar(),
      'data'    => $data,
      'fecha'   => date('Y-m-d'),
      'eb_data'   => array(
        array(
          'feather' => 'upload',
          'route'   => 'admin.bodega.traslado',
          'tooltip' => 'Traslado a barra',
          'params'  => ['id_pedido' => 0]
        ),
        array(
          'feather' => 'gift',
          'tooltip' => 'Cortesías',
          'route'   => 'admin.gerencia.agregar_cortesia',
          'params'  => ['id_pedido' => 0]
        ),
        array(
          'feather' => 'plus-circle',
          'tooltip' => 'Venta',
          'route'   => 'admin.bodega.venta_bodega',
          'params'  => []
        ),
        array(
          'feather' => 'dollar-sign',
          'tooltip' => 'Descarga de efectivo',
          'route'   => 'admin.cobrador.descarga_efectivo',
          'params'  => []
        )
      )
    ]);
  }

  public function pedido_para_despachar($id_pedido) {
    $pedido = pedidosModel::where('id', $id_pedido)->get()[0];

    $mesero = UserAdmin::where('id', Auth::id())->get()[0];
    if ($mesero->roleUS == 10) {
      $str = "select * from admin_pedidos_detalle where id_pedido = $id_pedido and estado and aprobado = 0 and despachado = 0;";
      $detalle = DB::select($str);
      foreach($detalle as $item) {
          DB::table('admin_pedidos_detalle')->where('id', $item->id)->update([
            'aprobado' => 1
          ]);
      }
    }

    $str = "select ap.nombre, sum(apd.cantidad) cantidad, sum(apd.subtotal) subtotal, ap.id_tipo from admin_pedidos_detalle apd left join admin_productos ap on (apd.id_producto = ap.id) where apd.estado and apd.id_pedido = $id_pedido and apd.aprobado = 1 and despachado = 0 group by ap.nombre, ap.id_tipo order by ap.id_tipo;";
    $detalle = DB::select($str);

    Config::set('extra_button', true);

    return view('admin.bodega.por_despachar', [
      'menubar'   => $this->list_sidebar(),
      'id_pedido' => $id_pedido,
      'pedido'    => $pedido,
      'detalle'   => $detalle,
      'eb_data'   => (object) array(
        array(
          'feather' => 'arrow-left',
          'tooltip' => 'Regresar',
          'route'   => 'admin.bodega.despachar',
          'params'  => []
        ),
        array(
          'feather' => 'upload',
          'route'   => 'admin.bodega.traslado',
          'tooltip' => 'Traslado a barra',
          'params'  => ['id_pedido' => 0]
        ),
        array(
          'feather' => 'gift',
          'tooltip' => 'Cortesías',
          'route'   => 'admin.gerencia.agregar_cortesia',
          'params'  => ['id_pedido' => 0]
        ),
        array(
          'feather' => 'plus',
          'tooltip' => 'Venta',
          'route'   => 'admin.bodega.venta_bodega',
          'params'  => []
        ),
        array(
          'feather' => 'dollar-sign',
          'tooltip' => 'Descarga de efectivo',
          'route'   => 'admin.cobrador.descarga_efectivo',
          'params'  => []
        )
      )
    ]);
  }

  public function despachar_pedido($id_pedido) {
    $str = "select * from admin_pedidos_detalle where id_pedido = $id_pedido and estado and aprobado = 1 and despachado = 0;";
    $detalle = DB::select($str);
    foreach($detalle as $item) {
        DB::table('admin_pedidos_detalle')->where('id', $item->id)->update([
          'despachado' => 1
        ]);
    }

    DB::table('admin_pedidos')->where('id', $id_pedido)->update([
      'id_estado' => 5
    ]);

    return redirect('admin/despachar')->with('success','Guardado correctamente!');
  }

  public function ingresos($fecha = null) {
    $action   = @$_POST['action'];
    $fecha    = $fecha ?: (@$_POST['fecha'] ?: date('Y-m-d'));
    $data = inventarioIngresosModel::where('fecha', $fecha)->get();

    if ($action) {
      $edit   = true;

      if ($action == 1) {
        foreach($_POST['ingresos'] as $key => $item) {
          $model = inventarioIngresosModel::create([
            'id_producto' => $key,
            'ingreso'     => $_POST['ingresos'][$key][0] ?: 0,
            'fecha'       => $_POST['fecha']
          ]);
          $model->timestamps = false;
          $model->save();
        }

        $action = 2;
      } elseif ($action == 2) {
        foreach($_POST['ingresos'] as $key => $item) {
          $check = inventarioIngresosModel::where('fecha', $fecha)->where('id_producto', $key)->get();
          if (count($check)) {
            DB::table('admin_inventario_ingresos')->where('fecha', $_POST['fecha'])->where('id_producto', $key)->update([
              'id_producto' => $key,
              'ingreso'     => $_POST['ingresos'][$key][0] ?: 0
            ]);
          } else {
            $model = inventarioIngresosModel::create([
              'id_producto' => $key,
              'ingreso'     => $_POST['ingresos'][$key][0] ?: 0,
              'fecha'       => $_POST['fecha']
            ]);
            $model->timestamps = false;
            $model->save();
          }
        }
      }
    } else {
      $edit   = false;
      $action = 1;
    }

    $str = "select fecha from admin_inventario where fecha < '$fecha' order by fecha desc limit 1;";
    $ultima_fecha = DB::select($str);
    $ultima_fecha = count($ultima_fecha) > 0 ? $ultima_fecha[0]->fecha : '';

    $str = "select distinct ap.id, ap.nombre, ai.cantidad_final final, aii.ingreso, (ai.cantidad_final + aii.ingreso) final_total, ap.id_tipo from admin_productos ap left join admin_inventario_ingresos aii on (ap.id = aii.id_producto and aii.fecha = '$fecha') left join (select id_producto, cantidad_inicial, cantidad_final, recarga, fecha from admin_inventario) ai on (ap.id = ai.id_producto and ai.fecha = '$ultima_fecha') where ap.estado order by ap.id_tipo, ap.orden;";
    // echo $str; exit();
    $ingreso = DB::select($str);

    if (count($data) > 0) {
      $edit   = true;
      $action = 2;
    }

    return view('admin.bodega.ingresos', [
      'menubar' => $this->list_sidebar(),
      'edit'    => $edit,
      'fecha'   => $fecha,
      'action'  => $action,
      'ingreso' => $ingreso
    ]);
  }

  public function recarga_inventario($id_producto, $cantidad, $fecha) {
    $inventario  = inventarioModel::where('fecha', $fecha)->where('id_producto', $id_producto)->get()[0];
    $recarga     = $inventario->recarga + $cantidad;
    $actualizado = DB::table('admin_inventario')->where('fecha', $fecha)->where('id_producto', $id_producto)->update([
      'recarga' => $recarga
    ]);

    $data = array(
      'actualizado' => $actualizado,
      'recarga'     => $recarga
    );

    return json_encode($data);
  }

  public function venta_bodega() {
      $model = pedidosModel::create([
        'id_tipo'    => 2,
        'id_usuario' => Auth::id(),
        'fecha'      => date('Y-m-d'),
        'hora'       => date('h:i:s'),
        'id_estado'  => 4,
      ]);
      $model->timestamps = false;
      $model->save();

      return redirect('admin/agregar_productos/' . $model->id);
  }

  public function traslado($id_pedido = 0) {
    if (strtotime(config('global.horario_cierre')) > strtotime(date('H:i:s'))) {
      $fecha = date('Y-m-d', strtotime("-1 day", strtotime(date('Y-m-d'))));
    } else {
      $fecha = date('Y-m-d');
    }

    $dia_despues = date('Y-m-d', strtotime("+1 day", strtotime($fecha)));
    $str = "select distinct ap.id, ap.nombre, ap.precio, ap.mixers, ap.id_tipo, atw.color, (if (ai.cantidad_inicial is null, 0, ai.cantidad_inicial) + if (ai.recarga is null, 0, ai.recarga)) - if (producto_vendido.cantidad is null, 0, producto_vendido.cantidad) stock,  producto_mas_vendido.cantidad mas_vendido from admin_productos ap left join admin_tipo_waro atw on (ap.id_tipo = atw.id) left join admin_inventario ai on (ap.id = ai.id_producto and ai.fecha = '$fecha') left join (select id_producto, sum(cantidad) cantidad from admin_pedidos_detalle where aprobado and despachado and estado and created_at between '$fecha " . config('global.horario_apertura') . "' and '$dia_despues " . config('global.horario_cierre') . "' group by id_producto) producto_vendido on (ap.id = producto_vendido.id_producto) left join (select id_producto, sum(cantidad) cantidad from admin_pedidos_detalle where aprobado and despachado and estado group by id_producto) producto_mas_vendido on (ap.id = producto_mas_vendido.id_producto) where ap.estado and ap.id_tipo <> 8 order by mas_vendido desc;";
    $productos = DB::select($str);

    $str = "select distinct ap.id, ap.nombre, ap.precio, ap.mixers, ap.id_tipo, atw.color, (if (ai.cantidad_inicial is null, 0, ai.cantidad_inicial) + if (ai.recarga is null, 0, ai.recarga)) - if (producto_vendido.cantidad is null, 0, producto_vendido.cantidad) stock,  producto_mas_vendido.cantidad mas_vendido from admin_productos ap left join admin_tipo_waro atw on (ap.id_tipo = atw.id) left join admin_inventario ai on (ap.id = ai.id_producto and ai.fecha = '$fecha') left join (select id_producto, sum(cantidad) cantidad from admin_pedidos_detalle where aprobado and despachado and estado and created_at between '$fecha " . config('global.horario_apertura') . "' and '$dia_despues " . config('global.horario_cierre') . "' group by id_producto) producto_vendido on (ap.id = producto_vendido.id_producto) left join (select id_producto, sum(cantidad) cantidad from admin_pedidos_detalle where aprobado and despachado and estado group by id_producto) producto_mas_vendido on (ap.id = producto_mas_vendido.id_producto) where ap.estado and ap.id_tipo = 8 order by mas_vendido desc;";
    $mixers = DB::select($str);

    $data = [];
    foreach($productos as $key => $item) {
      $data[] = $item;
    }
    foreach($mixers as $key => $item) {
      $data[] = $item;
    }

    $detalle = DB::select("select apd.id, apd.id_pedido, ap.nombre, apd.cantidad, apd.subtotal, ap.mixers from admin_pedidos_detalle apd left join admin_productos ap on (apd.id_producto = ap.id) where apd.estado and apd.id_pedido = '$id_pedido' and apd.contable in (2) and apd.estado and apd.aprobado = 0 and apd.despachado = 0 order by id desc;");

    $str = "select * from admin_pedidos where id = $id_pedido;";
    $pedido = DB::select($str);

    Config::set('extra_button', true);

    return view('admin.bodega.traslado', [
      'menubar' => $this->list_sidebar(),
      'data'      => $data,
      'id_pedido' => $id_pedido,
      'detalle'   => $detalle,
      'pedido'    => $pedido,
      'eb_data'   => array(
        array(
          'feather' => 'upload',
          'route'   => 'admin.bodega.traslado',
          'tooltip' => 'Traslado a barra',
          'params'  => ['id_pedido' => 0]
        ),
        array(
          'feather' => 'gift',
          'tooltip' => 'Cortesías',
          'route'   => 'admin.gerencia.agregar_cortesia',
          'params'  => ['id_pedido' => 0]
        ),
        array(
          'feather' => 'plus-circle',
          'tooltip' => 'Venta',
          'route'   => 'admin.bodega.venta_bodega',
          'params'  => []
        ),
        array(
          'feather' => 'dollar-sign',
          'tooltip' => 'Descarga de efectivo',
          'route'   => 'admin.cobrador.descarga_efectivo',
          'params'  => []
        )
      )
    ]);
  }

  public function cargar_traslado($id_pedido, $id_producto, $cantidad) {
    if (!$id_pedido) {
      $pedido = pedidosModel::create([
        'id_tipo'    => 5,
        'id_usuario' => Auth::id(),
        'fecha'      => date('Y-m-d'),
        'hora'       => date('h:i:s'),
        'id_estado'  => 2,
      ]);
      $pedido->save();
      $id_pedido = $pedido->id;
    }
    $str = "select * from admin_productos where id = $id_producto;";
    $prod = DB::select($str);

    $model = pedidosDetalleModel::create([
      'id_pedido'   => $id_pedido,
      'id_producto' => $id_producto,
      'cantidad'    => $cantidad,
      'subtotal'    => $prod[0]->precio * $cantidad,
      'contable'    => 0
    ]);
    $guardado = $model->save() ? true : false;

    $data = array(
      'id_det'   => $model->id,
      'nombre'   => $prod[0]->nombre,
      'subtotal' => number_format($prod[0]->precio * $cantidad, 2,".",","),
      'cantidad' => $cantidad,
      'guardado' => $guardado,
      'id_pedido'   => $id_pedido
    );

    return json_encode($data);
  }

  public function enviar_traslado($id_pedido) {
    $pedido = pedidosModel::where('id', $id_pedido)->get()[0];
    $str = "select * from admin_pedidos_detalle where id_pedido = $id_pedido and estado and aprobado = 0 and despachado = 0;";
    $detalle = DB::select($str);
    foreach($detalle as $item) {
        DB::table('admin_pedidos_detalle')->where('id', $item->id)->update([
          'aprobado'   => 1,
          'despachado' => 1
        ]);
    }

    DB::table('admin_pedidos')->where('id', $id_pedido)->update([
      'id_estado' => 6
    ]);

    $mesero = UserAdmin::where('id', Auth::id())->get()[0];

    return redirect('admin/inventario');
  }
}
