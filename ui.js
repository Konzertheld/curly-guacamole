window.onload = function () {
    // Select tasks
    $(".task h2").on("click", null, null, function (e) {
        $(this).parent().toggleClass("selected");
    });

    $("h1").on("click", null, null, function (e) {
        var oldtext = $("#new-event-input").val();
        $("#new-event-input").val(oldtext + $(this).parent().attr("data-date"));
        $("#new-event-input").focus();
    });

    // Keyboard shortcuts
    $("body").on("keypress", null, null, function (e) {
        var tag = e.target.tagName.toLowerCase();
        if(tag === "input" || tag === "textarea" || ($("#new-event-input")[0].value.trim() !== "")) {
            // react to enter here
            // call another function that can also be triggered by clicking on a day heading
            // when clicking on a day heading, append date of that day to the input field and send TODO
            if(e.which === 13) {
                processTask(e);
            }
            return;
        }
        // Which tasks operate on?
        task_ids = [];
        commands = [];
        additional = null;
        selected_days = $(".selected");

        selected_days.each(function () {
            task_ids.push(this.id.substring(5));
        });

        switch (e.which) {
            // Note that the order might be important
            case 84:
            case 68:
                // D for done and move to today, also for uppercase T
                commands = ["today", "done"];
                break;
            case 100:
                // d for done
                commands = ["done"];
                break;
            case 127:
                // del
                commands = ["delete"];
                break;
            // Durations
            case 49:
                // 1 for set duration to 1x 15min
            case 50:
                // 2 for set duration to 2x 15min = 30min etc
            case 51:
            case 52:
            case 53:
            case 54:
            case 55:
            case 56: // 2 hours
                commands = ["duration"];
                additional = (e.which - 48) * 15 * 60;
                break;
            // Move
            // tricky code: use switch fallthrough but avoid overriding variable
            // for uppercase letters, add "done"
            // Q W E R for the first row
            case 81:
            case 113:
                day_id = 1;
            case 87:
            case 119:
                if(typeof day_id === "undefined") day_id = 2;
            case 69:
            case 101:
                if(typeof day_id === "undefined") day_id = 3;
            case 82:
            case 114:
                if(typeof day_id === "undefined") day_id = 4;
            // Z U I O for the second row
            case 90:
            case 122:
                if(typeof day_id === "undefined") day_id = 5;
            case 85:
            case 117:
                if(typeof day_id === "undefined") day_id = 6;
            case 73:
            case 105:
                if(typeof day_id === "undefined") day_id = 7;
            case 79:
            case 111:
                if(typeof day_id === "undefined") day_id = 8;
                additional = $("#day-" + day_id).attr("data-date");
                if(e.shiftKey) {
                    commands = ["done", "move"]
                }
                else {
                    commands = ["move"];
                }
                break;
            // Today
            case 116:
                commands = ["today"];
                break;
            case 32:
                // space for abort all
                selected_days.removeClass("selected");
                break;
            default:
                return;
        }
        if (typeof commands !== "undefined") {
            $.ajax({
                url: "/planer/_api.php",
                data: {
                    task_ids: task_ids.join(","),
                    commands: commands.join(","),
                    additional_data: additional
                },
                success: function (result) {
                    window.location.reload(true);
                }
            });
        }
    });
};

function processTask(e) {
    var data = {
        commands: "add",
        task_string: $("#new-event-input")[0].value.trim()
    };
    // check for day shortcuts
    skip_shift_date = false;
    const regex = /(?<= )([QWERTZUIOYqwertzuioy])([+-][0-9]+)?$/g;
    const shortcut_result = regex.exec(data.task_string);
    if(shortcut_result !== null) {
        switch(shortcut_result[1].toUpperCase()) {
            case "T":
                date = $("#today").attr("data-date");
                break;
            case "Y":
                date = $("#today").attr("data-yesterday");
                break;
            case "Q":
                date = $("#day-1").attr("data-date");
                break;
            case "W":
                date = $("#day-2").attr("data-date");
                break;
            case "E":
                date = $("#day-3").attr("data-date");
                break;
            case "R":
                date = $("#day-4").attr("data-date");
                break;
            case "Z":
                date = $("#day-5").attr("data-date");
                break;
            case "U":
                date = $("#day-6").attr("data-date");
                break;
            case "I":
                date = $("#day-7").attr("data-date");
                break;
            case "O":
                date = $("#day-8").attr("data-date");
                break;
        }
        // replace the letter with the date
        data.task_string = data.task_string.substring(0, data.task_string.length - shortcut_result[0].length) + date;
        if(shortcut_result[2] !== undefined) {
            data.task_string += shortcut_result[2];
        }
        skip_shift_date = true;
    }
    // process shift key
    // this appends "today". Because only the first appearance of a date is processed, a manually added date will prevail
    if(e.shiftKey) {
        data.done = true;
        if(!skip_shift_date) {
            data.task_string += " " + $("#today").attr("data-date");
        }
    }
    $.ajax({
        url: "/planer/_api.php",
        data: data,
        success: function (result) {
            window.location.reload(true);
        }
    });
}