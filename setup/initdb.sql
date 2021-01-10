CREATE TABLE "tasks" (
	"id"	INTEGER NOT NULL UNIQUE,
	"description"	TEXT DEFAULT NULL COLLATE NOCASE,
	"duration"	INTEGER NOT NULL,
	"date"	TEXT NOT NULL,
	"advance_span"	INTEGER,
	"deadline_day"	INTEGER DEFAULT NULL,
	"deadline_time"	INTEGER DEFAULT NULL,
	"recurrence_type"	INTEGER DEFAULT NULL,
	"recurrence_days"	INTEGER DEFAULT NULL,
	"done"	INTEGER DEFAULT 0,
	"google_id"	TEXT DEFAULT NULL UNIQUE,
	"rtm_id"	TEXT DEFAULT NULL UNIQUE,
	PRIMARY KEY("id" AUTOINCREMENT)
);

CREATE TABLE "tags" (
	"name"	TEXT NOT NULL UNIQUE,
	PRIMARY KEY("name")
);

CREATE TABLE "tasks_tags" (
	"task_id"	INTEGER NOT NULL,
	"tag_name"	TEXT NOT NULL
);

INSERT INTO tags (name) VALUES ("overdue");
INSERT INTO tags (name) VALUES ("additional");
INSERT INTO tags (name) VALUES ("orphaned");
