{{-- @inject('helper', 'App\Http\Helpers\helpers') --}}
@extends('admin.layouts.app')
@section('main-content')
@php $criti=[];@endphp
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <form id="ingresos_form" method="POST" action="/admin/ingresos{{isset($data->id) ? '/'.$data->id : ''}}" onsubmit="event.preventDefault(); realizarAccion('ingresos_form')">
          @csrf
          @if($edit)
              @method('PUT')
          @endif

          <input type="hidden" name="action" value="{{$action}}">
          <div class="card-header bg-dark">
            <h3 class="card-title">
              INGRESOS DE PRODUCTOS
            </h3>

            <h3 class="card-title">
              <label class="d-inline-block mx-1">Fecha:</label>
              <input type="date" class="form-control d-inline-block w-auto" id="fecha" name="fecha" value="{{$fecha}}" onchange="cambioDeFecha(this.value);" />
              </a>
            </h3>
          </div>
          <div class="card-body">
            <div class="row" id="panel-alerta">
              <div class="col-12 bg-warning p-2 text-center">
                <h1>Gira el dispositivo para tener una mejor visualización</h1>
              </div>
            </div>
            <div class="row" id="panel-principal">
            <div class="row">
              <div class="col-3 border bg-dark">
                <label class="text-light py-1 fs-3"> Producto </label>
              </div>
              <div class="col-3 border bg-dark">
                <label class="text-light py-1 fs-3"> Inventario anterior </label>
              </div>
              <div class="col-3 border bg-dark">
                <label class="text-light py-1 fs-3"> Compras </label>
              </div>
              <div class="col-3 border bg-dark">
                <label class="text-light py-1 fs-3"> Inventario final </label>
              </div>

              @foreach ($ingreso as $item)
                <div class="col-3 border">
                  <label class="fs-3 pt-1"> {{$item->nombre}} </label>
                </div>
                <div class="col-3 border">
                  <label class="fs-3 pt-1" id="inicial_{{$item->id}}"> {{$item->final ?: 0}} </label>
                </div>
                <div class="col-3 border">
                  <input class="form-control my-1" id="ingresos" rel="{{$item->id}}" name="ingresos[{{$item->id}}][]" type="text" value="{{$item->ingreso ?: 0}}" onClick="this.select();" autocomplete="off" />
                </div>
                <div class="col-3 border">
                  <label class="fs-3 pt-1" id="final_{{$item->id}}"> {{$item->final_total ?: 0}} </label>
                </div>
              @endforeach
            </div>
            <div class="row">
              <div class="col-12 col-sm-4 mx-auto border">
                  <button class="btn btn-primary my-1 w-100" style="">
                      {{ @$edit ? 'Actualizar datos' : 'Guardar'}}
                  </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script type="text/javascript">
    function cambioDeFecha(fecha) {
      if (!isNaN(Number(new Date(fecha)))) {
        window.location = "/admin/ingresos/" + encodeURIComponent(fecha)
      }
    }

    function realizarAccion(formulario){
      $('#' + formulario).attr('onsubmit', '').submit();
    }

    $(document).ready(function(){
      $('input#ingresos').keyup(function() {
        var id = $(this).attr('rel');
        var inicial = parseInt($('#inicial_' + id).html());
        var final   = parseInt($(this).val()) + parseInt($('#inicial_' + id).html())
        $('#final_' + id).html(final)
      });

      $(window).resize(function(){
        ancho = $(window).width();
        if (ancho < 425) {
          $('#panel-alerta').show();
          $('#panel-principal').hide();
        } else {
          $('#panel-alerta').hide();
          $('#panel-principal').show();
        }
      })
    });

    var ancho = $(window).width();
    if (ancho < 550) {
      $('#panel-alerta').show();
      $('#panel-principal').hide();
    } else {
      $('#panel-alerta').hide();
      $('#panel-principal').show();
    }
  </script>
  @component('admin.components.messagesForm')
  @endcomponent
@endsection
