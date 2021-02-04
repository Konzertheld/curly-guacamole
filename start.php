<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
	<link rel="stylesheet" href="style.css">
    <title>Planer</title>
</head>
<body>
<div id="days-container">
<?php $i = 1; foreach($days as $day => $tasks): ?>
    <section class="day" id="day-<?php echo $i; ?>">
		<h1 class="day-heading"><?php echo $day; ?></h1>
		<ul><?php foreach($tasks as $task): ?>
			<li class="task<?php if($task->done) echo ' done'; ?>"><h2 class="task-heading"><?php echo $task->description; ?></h2>
				<span class="deadline"><?php echo $task->deadline_day; ?></span>
				<span class="duration"><?php echo $task->duration / 60; ?> min</span>
			</li>
			<?php endforeach; ?>
		</ul>
	</section>
<?php $i++; endforeach; ?>
</div>
</body>
</html>