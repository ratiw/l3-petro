<?php

namespace Petro;

class Comment
{
	protected static $table = 'comments';

	public static function render($app, $ref_id, $title = 'Comments')
	{
		if ( ! isset($app))
		{
			throw new \Exception('Invalid argument. $app is not set!');
		}

		$query = \DB::query(
			'SELECT '.static::$table.'.*, users.username FROM '.static::$table.', users'.
			' WHERE '.static::$table.'.user_id = users.id'.
			' AND '.static::$table.'.app = "'.$app.'"'.
			' AND '.static::$table.'.ref_id = '.$ref_id.
			' ORDER BY '.static::$table.'.created_at asc'
		);

		$data['title']  = $title;
		$data['app']    = $app;
		$data['ref_id'] = $ref_id;
		$data['total_comments'] = count($query);

		if ($data['total_comments'] <= 0)
		{
			$data['comments'] = str_replace('{text}', 'No comment yet.', \Config::get('petro::petro.template.comment.empty'));
		}
		else
		{
			$t = \Config::get('petro::petro.template.comment.item');
			$out = '';
			foreach ($query as $item)
			{
				$author = isset($item['username']) ? $item['username'] : 'Anonymous';
				$date = $item['created_at'];
				if ( ! empty($date))
				{
					// $date = (new \DateTime($item['created_at']))->format('Y-m-d H:i:s');
					$date = new \DateTime($item['created_at']);
					$date = $date->format('Y-m-d H:i:s');
				}
				$cost = empty($item['number']) ? '' : number_format($item['number']);

				$out .= str_replace(array('{comment_id}', '{comment_author}', '{comment_date}', '{comment_text}', '{comment_cost}'),
					array($item['id'], $author, $date, nl2br($item['text']), $cost),
					$t);
			}
			$data['comments'] = $out;
		}

		$data['last_url'] = \URL::current();

		return \View::make('petro::comment', $data);
	}

	public static function save($data = array())
	{
		if (empty($data)) return false;

		$comment = Comment::create($data);
		return $comment;
	}
}