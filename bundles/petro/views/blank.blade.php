@layout('petro::layout.common')

@section('header')
  <div class="page_header">
    {{isset($breadcrumbs) ? $breadcrumbs : ''}}
    <div class="row-fluid">
      <h1 class="pull-left" id="page_title">Page Title</h1>
      <div class="action_items pull-right">
        {{isset($action_items) ? $action_items : ''}}
      </div>
    </div>
  </div>
@endsection

@section('content')
@endsection

@section('footer')
    <hr />
    Footer of blank page here -- copyright &copy;
@endsection
