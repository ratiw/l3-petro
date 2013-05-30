<div class="grid_wrapper">
	<div class="grid_scopes">
		{{$scopes}}
	</div>{{--/grid_scopes--}}
	<div class="grid_top">
		{{$page_info}}
	</div>{{--/grid_top--}}
	<div class="paginated_collection">
		<div class="paginated_collection_contents">
			<div class="index_content">
				{{$grid}}
			</div>{{--index_content--}}
			<div id="index_footer">
				{{$pagination}}
			</div>{{--/index_footer--}}
		</div>{{--paginated_collection_contents--}}
	</div>{{--/paginated_collection--}}
</div>{{--/grid_wrapper--}}


