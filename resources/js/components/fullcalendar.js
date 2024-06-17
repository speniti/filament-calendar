import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import googleCalendarPlugin from '@fullcalendar/google-calendar';
import interactionPlugin from '@fullcalendar/interaction';

export default function calendar(options) {
  return {
    calendar: null,

    async init() {
      this.calendar = new Calendar(this.$root, {
        plugins: [
          dayGridPlugin,
          googleCalendarPlugin,
          interactionPlugin,
          timeGridPlugin,
        ],

        locales: [
          {
            code: 'it',
            week: {
              dow: 1,
              doy: 4,
            },
            buttonText: {
              prev: 'Prec',
              next: 'Succ',
              today: 'Oggi',
              month: 'Mese',
              week: 'Settimana',
              day: 'Giorno',
              list: 'Agenda',
            },
            weekText: 'Sm',
            allDayText: 'Tutto il giorno',
            moreLinkText: n => '+altri ' + n,
            noEventsText: 'Non ci sono eventi da visualizzare',
          },
        ],
        locale: document.documentElement.lang,

        aspectRatio: 1.3,
        initialView: 'timeGridWeek',

        nowIndicator: true,

        slotLabelFormat: {
          hour: '2-digit',
          minute: '2-digit',
          meridiem: false,
          omitZeroMinute: false,
        },
        headerToolbar: {
          right: 'prev,next today',
          center: 'title',
          left: 'dayGridMonth,timeGridWeek',
        },

        eventDisplay: 'block',
        eventTimeFormat: {
          hour: '2-digit',
          minute: '2-digit',
          meridiem: false,
        },
        dayMaxEventRows: true,

        selectable: true,

        ...options,

        eventSources: [
          this.googleCalendarEventSource(options),
          {
            events: ({ start, end }, resolve, reject) => {
              this.$wire.fetchEvents(start, end).then(resolve).catch(reject);
            },
          },
        ],

        eventDrop: ({ event, revert }) => this.edit(event, revert),
        eventResize: ({ event, revert }) => this.edit(event, revert),
        select: ({ start, end }) => this.$wire.select(start, end),
      });

      this.calendar.render();
    },

    googleCalendarEventSource(options) {
      const { googleCalendarApiKey, googleCalendarId } = options;
      delete options.googleCalendarId;

      if (!googleCalendarApiKey) {
        return undefined;
      }

      return { googleCalendarId, className: 'fc-event-google' };
    },

    edit({ id, start, end }, revert) {
      this.$wire.edit(id, start, end).catch(revert);
    },
  };
}
