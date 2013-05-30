<div class="comments panel">
	<p>{{ $title }} ({{ isset($total_comments) ? $total_comments : 0 }})</p>
	<div class="panel_contents">
		{{ $comments }}
		{{ Form::open(URI::segment(1).'/comment', 'POST', array('class' => 'active_admin_comment', 'method' => 'post')) }}
		<input type="hidden" id="comment_app" name="comment_app" value="{{$app}}">
		<input type="hidden" id="comment_ref_id" name="comment_ref_id" value="{{$ref_id}}">
		<input type="hidden" id="comment_type" name="comment_type" value="1">
		<input type="hidden" id="last_url" name="last_url" value="{{ $last_url }}">
		<div class="comment_inputs">
			<div class="control-group">
				<div>
					<textarea style="width:96%" cols="80" rows="8" id="comment_text" name="comment_text"></textarea>
				</div>
				<div>
					{{ 'Related Cost' }} <input type="text" id="cost" name="cost" value="" style="width:100px">
					<button type="submit" id="add-comment-button" class="btn pull-right">Add Comment</button>
				</div>
			</div>

		</div>
		{{ Form::close() }}
	</div>
</div>
