@if (is_array($item))
	<li class="menu">
		<span>{{admin_trans($key)}}</span>
		<ul>
			@foreach ($item as $k => $subitem)
				<?php echo view("administrator::partials.menu_item", array(
					'item' => $subitem,
					'key' => $k,
					'settingsPrefix' => $settingsPrefix,
					'pagePrefix' => $pagePrefix
				))?>
			@endforeach
		</ul>
	</li>
@else
	<li class="item">
		@if (strpos($key, $settingsPrefix) === 0)
			<a href="{{route('admin_settings', array(substr($key, strlen($settingsPrefix))))}}">{{admin_trans($item)}}</a>
		@elseif (strpos($key, $pagePrefix) === 0)
			<a href="{{route('admin_page', array(substr($key, strlen($pagePrefix))))}}">{{admin_trans($item)}}</a>
		@else
			<a href="{{route('admin_index', array($key))}}">{{admin_trans($item)}}</a>
		@endif
	</li>
@endif
