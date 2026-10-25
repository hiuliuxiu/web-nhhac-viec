/**
 * F03 - GÓC NHÌN LỊCH (FullCalendar)
 *
 * Trải công việc lên lịch theo hạn chót. Khởi tạo đúng một lần, các lần vẽ lại
 * sau chỉ thay dữ liệu sự kiện.
 */
class CalendarView extends View {

    constructor(context) {
        super(context);
        this.calendar = null;
    }

    ensure() {
        if (this.calendar) return this.calendar;

        const host = this.el('calendarHost');
        if (!host || typeof FullCalendar === 'undefined') return null;

        this.calendar = new FullCalendar.Calendar(host, {
            initialView: 'dayGridMonth',
            locale:      'vi',
            height:      'auto',
            firstDay:    1,                       // tuần bắt đầu từ thứ Hai

            headerToolbar: {
                left:   'prev,next today',
                center: 'title',
                right:  'dayGridMonth,timeGridWeek,listWeek'
            },

            buttonText: {
                today: 'Hôm nay',
                month: 'Tháng',
                week:  'Tuần',
                list:  'Danh sách'
            },

            noEventsText:    'Không có công việc nào tới hạn trong khoảng này',
            dayMaxEvents:    3,
            moreLinkText:    (n) => '+ ' + n + ' việc nữa',
            eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },

            // Bấm vào một sự kiện thì mở hộp thoại sửa công việc tương ứng.
            eventClick: (info) => {
                info.jsEvent.preventDefault();
                this.app.openTaskModal(parseInt(info.event.id, 10));
            },

            // Bấm vào một ô ngày trống thì tạo công việc với hạn chót là ngày đó.
            dateClick: (info) => {
                this.app.openTaskModal(null, { dueDate: info.dateStr });
            }
        });

        this.calendar.render();
        return this.calendar;
    }

    render() {
        const calendar = this.ensure();
        if (!calendar) return;

        calendar.removeAllEvents();
        calendar.addEventSource(this.buildEvents());

        // FullCalendar cần đo lại kích thước khi khung chứa vừa được hiện ra.
        calendar.updateSize();
    }

    buildEvents() {
        return this.store.filteredTasks()
            .filter(task => task.due_at)
            .map(task => {
                const color = this.eventColor(task);

                return {
                    id:              String(task.id),
                    title:           task.title,
                    start:           task.due_at,
                    allDay:          false,
                    backgroundColor: color,
                    borderColor:     color,
                    textColor:       '#ffffff',
                    classNames:      task.status === 'done' ? ['is-done-event'] : []
                };
            });
    }

    /** Màu sự kiện phản ánh mức khẩn cấp, giống hệt màu thẻ trên Kanban. */
    eventColor(task) {
        if (task.status === 'done')        return '#10b981';
        if (task.due_state === 'overdue')  return '#e5484d';
        if (task.due_state === 'due_soon') return '#f59e0b';

        return Catalog.statusColor(task.status);
    }
}
