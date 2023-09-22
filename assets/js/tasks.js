function handle_tasks() {
    const sortable = document.getElementById('sortable');
    let draggingTask = null;

    if (null !== sortable) {
        sortable.addEventListener('dragstart', (e) => {
            const task = e.target;
            draggingTask = task;
            task.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', task.id);
        });

        sortable.addEventListener('dragover', (e) => {
            e.preventDefault();
            const taskBeingHovered = e.target;

            if (!taskBeingHovered.classList.contains('task')) return;

            const rect = taskBeingHovered.getBoundingClientRect();
            const midY = rect.top + rect.height / 2;

            const isDraggingDown = e.clientY > midY;

            if (draggingTask !== taskBeingHovered) {
                if (isDraggingDown) {
                    sortable.insertBefore(draggingTask, taskBeingHovered.nextSibling);
                } else {
                    sortable.insertBefore(draggingTask, taskBeingHovered);
                }
            }
        });

        sortable.addEventListener('dragend', (e) => {
            const taskId = e.dataTransfer.getData('text/plain');
            const task = document.getElementById(taskId);

            if (task) {
                task.classList.remove('dragging');
            }

            draggingTask = null;
        });

        document.addEventListener('dragstart', (e) => {
            if (e.target.classList.contains('task')) {
                e.target.classList.add('dragging');
            }
        });

        document.addEventListener('dragend', (e) => {
            const tasks = document.querySelectorAll('.task');
            tasks.forEach((task) => {
                task.classList.remove('dragging');
            });
        });
    }

    $('.todo-list').on('click', '.delete-task', function () {
        let task = $(this).closest('.task'); // Use closest to find the parent .task element
        let taskId = $(this).attr('data-task-id');
        $.ajax({
            url: '/panel/tasks/delete-task/' + taskId,
            method: 'POST',
            success: function (result) {
                task.remove();
            },
            error: function (xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    });

    $('.todo-list').on('change', '.form-check-input', function () {
        let taskId = $(this).next('div').find('button').attr('data-task-id');
        let check = $(this);
        $.ajax({
            url: '/panel/tasks/mark-as-done/' + taskId,
            method: 'POST',
            success: function (result) {
                console.log("A");
                if (check.prop('checked')) {
                    check.closest('.task').addClass('crossed-out');
                } else {
                    check.closest('.task').removeClass('crossed-out');
                }
            },
            error: function (xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    });

    $(function () {
        $("#sortable").sortable({
            update: function (event, ui) {
                // Get the new order of tasks
                var newOrder = [];
                $("#sortable .task").each(function (index) {
                    var taskId = $(this).attr("id").replace("task-", "");
                    newOrder.push({id: taskId, position: index + 1});
                });

                // Serialize the newOrder array to JSON
                var jsonData = JSON.stringify({newOrder: newOrder});

                // Send the new order via AJAX
                $.ajax({
                    url: '/panel/tasks/update-task-order',
                    method: 'POST',
                    contentType: 'application/json',
                    data: jsonData,
                    success: function (response) {
                        console.log(response.message);
                    },
                    error: function (xhr, status, error) {
                        console.log(xhr.responseText);
                    }
                });
            }
        });
    });

    $('#task-form').submit(function (e) {
        e.preventDefault();

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: $(this).serialize(),
            success: function (response) {
                $('#tasks_form_description').val('');
                // Create a new task template for the task
                let taskTemplate = $.parseHTML($('#task-template').html());

                $(taskTemplate).attr('id', 'task-' + response.id);
                $(taskTemplate).find('span').text(response.description);
                $(taskTemplate).find('.delete-task').attr('data-task-id', response.id);
                $('#task-template').before(taskTemplate);
            },
            error: function (xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    });
}
export {handle_tasks}