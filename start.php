<?php require_once("_ui.php"); ?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" href="style.css">
	<title>Planer</title>
	<script src="vendor/jquery-3.5.1.min.js"></script>
	<script src="ui.js"></script>
</head>
<body>
<header>
	<a href="/planer"><h1 id="today" data-date="<?php echo $today; ?>">Heute ist <?php echo day_weekday(); ?>, der <?php echo day_label(date("Y-m-d"), true, true); ?></h1></a>
	<section id="navigation">
		<a href="/planer?from=<?php echo $jump_view_back; ?>"><<</a>
		<a href="/planer?from=<?php echo $jump_day_back; ?>"><</a>
		<a href="/planer?from=<?php echo $today; ?>">Heute</a>
		<a href="/planer?from=<?php echo $jump_day_forward; ?>">></a>
		<a href="/planer?from=<?php echo $jump_view_forward; ?>">>></a>
	</section>
</header>

<section id="new-event">
	<input id="new-event-input" type="text" autofocus>
</section>

<section id="days-container">
	<?php $i = 1;
	foreach ($days as $day => $tasks): ?>
		<section class="day" data-date="<?php echo $day; ?>" id="day-<?php echo $i; ?>">
			<span class="shortcut-tip"><?php echo $day_shortcut_assignments[$i]; ?></span><h1 class="day-heading"><?php echo day_label($day); ?></h1>
			<ul><?php foreach ($tasks as $task): ?>
					<li id="task-<?php echo $task->id; ?>" class="task <?php if ($task->done) echo 'done'; else echo 'undone'; ?>"><h2 class="task-heading"><?php echo $task->description; ?></h2>
						<span class="duration"><?php echo $task->duration / 60; ?> min</span>
						<?php if($task->deadline_day): ?><span class="deadline"><?php echo day_label_deadline($task->deadline_day, $day); ?></span><?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php $i++;
	endforeach; ?>
</section>
</body>
</html>