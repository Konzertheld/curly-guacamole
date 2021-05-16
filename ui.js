window.onload = function () {
    // Select tasks
    $(".task h2").on("click", null, null, function (e) {
        $(this).parent().toggleClass("selected");
    });

    // Keyboard shortcuts
    $("body").on("keypress", null, null, function (e) {
        var tag = e.target.tagName.toLowerCase();
        if(tag === "input" || tag === "textarea" || ($("#new-event-input")[0].value.trim() !== "")) {
            // react to enter here
            // call another function that can also be triggered by clicking on a day heading
            // when clicking on a day heading, append date of that day to the input field and send TODO
            if(e.which === 13) {
                processTask();
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
            case 68:
                // D for done and move to today
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
            // Q W E R for the first row
            // tricky code: use switch fallthrough but avoid overriding variable
            case 113:
                day_id = 1;
            case 119:
                if(typeof day_id === "undefined") day_id = 2;
            case 101:
                if(typeof day_id === "undefined") day_id = 3;
            case 114:
                if(typeof day_id === "undefined") day_id = 4;
            // Z U I O for the second row
            case 122:
                if(typeof day_id === "undefined") day_id = 5;
            case 117:
                if(typeof day_id === "undefined") day_id = 6;
            case 105:
                if(typeof day_id === "undefined") day_id = 7;
            case 111:
                if(typeof day_id === "undefined") day_id = 8;
                additional = $("#day-" + day_id).attr("data-date");
                commands = ["move"];
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

function processTask() {
    $.ajax({
        url: "/planer/_api.php",
        data: {
            commands: "add",
            task_string: $("#new-event-input")[0].value.trim()
        },
        success: function (result) {
            window.location.reload(true);
        }
    });
}