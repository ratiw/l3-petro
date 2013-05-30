@layout('petro::layout.common')

@section('extra-script')
  <script>
    $(document).ready(function() {
      <!-- FIXME: Bootstrap will retreive content from location specified in href and display inside modal -->
      $('a.del-item').click(function(){
        $('#petro-confirm a#petro-confirm-button').attr('href', $(this).attr('url'));
      });

      $('.clear_filters_btn').click(function(){
        window.location.search = "";
        return false;
      });

    });
  </script>
@endsection

@section('header')
  <div class="page_header">
    <div class="row-fluid">
      <div class="pull-left">
        {{isset($breadcrumbs) ? $breadcrumbs : ''}}
        <h2 id="page_title">{{isset($page_title) ? $page_title : '&nbsp;'}}</h2>
      </div>
      <div class="action_items pull-right">
        {{isset($action_items) ? $action_items : ''}}
      </div>
    </div>
  </div>
@endsection

@section('content')
<div class="container-fluid">
{{-- check if there's notify msg. if so, display it --}}
@if (Session::has('notify'))
  <?php $notify = Session::get('notify') ?>
  <div class="row-fluid">
    <div class="alert <?php echo 'alert-'.$notify[0]; ?> fade in">
      <a class="close" data-dismiss="alert" href="#">&times;</a>
      {{ $notify[1] }}
    </div>
  </div>
@endif

{{-- error box --}}
@if (Session::has('errors'))
  <div class="row-fluid">
    <div id="alert-box" class="alert alert-error block-message error" data-alert="alert">
      <a class="close" data-dismiss="alert" href="#">&times;</a>
      <p><strong>Error!</strong></p>
      <ul>
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
    </div>
  </div>
@endif

<?php
  if ( ! isset($content)) $content = '&nbsp;';
?>

  <div class="row-fluid">
@if (isset($sidebars))
    <div class="span9">
      {{ $content }}
    </div>
    <div class="span3">
      {{ $sidebars }}
    </div>
@else
    {{ $content }}
@endif
  </div>{{-- /row-fluid --}}

</div>
@include('petro::confirm-box')

@endsection

@section('footer')
    <hr>
    Footer here -- copyright &copy;
@endsection
