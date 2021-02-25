window.onload = function() {
    // Select tasks
    $(".task h2").on("click", null, null, function (e) {
        $(this).parent().toggleClass("selected");
    });

    // Keyboard shortcuts
    $("body").on("keypress", null, null, function(e) {
        // Which tasks operate on?
        task_ids = [];
        $(".selected").each(function(){ task_ids.push(this.id.substring(5)); });

        switch (e.which) {
            case 68:
                // D for done and move to today
                break;
            case 100:
                // d for done
                $.ajax({
                        url: "/planer/_api.php",
                        data: {
                            task_ids: task_ids.join(","),
                            command: "done"
                        },
                        success: function (result) {
                            window.location.reload(true);
                        }
                    });
                break;
            case 49:
                // 1 for set duration to 1x 15min
            case 50:
                // 2 for set duration to 2x 15min = 30min etc
            case 51:
            case 52:
            case 53:
            case 54:
                break;
            case 32:
                // space for abort all
                $(".selected").removeClass("selected");
                break;
        }
    });
};