window.onload = function () {
    // Select tasks
    $(".task h2").on("click", null, null, function (e) {
        $(this).parent().toggleClass("selected");
    });

    // Keyboard shortcuts
    $("body").on("keypress", null, null, function (e) {
        // Which tasks operate on?
        task_ids = [];
        $(".selected").each(function () {
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
            case 49:
            // 1 for set duration to 1x 15min
            case 50:
            // 2 for set duration to 2x 15min = 30min etc
            case 51:
            case 52:
            case 53:
            case 54:
                commands = ["duration"];
                break;
            case 32:
                // space for abort all
                $(".selected").removeClass("selected");
                break;
        }
        if(typeof commands !== "undefined") {
            $.ajax({
                url: "/planer/_api.php",
                data: {
                    task_ids: task_ids.join(","),
                    commands: commands.join(",")
                },
                success: function (result) {
                    window.location.reload(true);
                }
            });
        }
    });
};