<div class="btn-group" data-toggle="buttons-radio">
@foreach ($scopes as $scope => $prop)
  @if ($scope == $selected_scope)
	<button class="scope btn disabled">
		{{$prop['label']}}<span class="count">&nbsp;({{$prop['count']}})</span>
	</button>
  @else
	<a class="scope btn" href="{{$prop['link']}}">{{$prop['label']}}<span class="count">&nbsp;({{$prop['count']}})</span></a>
  @endif
@endforeach
</div>