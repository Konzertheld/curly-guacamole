DELETE FROM tasks_tags WHERE rowid NOT IN (SELECT min(rowid) from tasks_tags group by task_id, tag_name)

