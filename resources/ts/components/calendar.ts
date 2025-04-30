import {
  Calendar,
  CalendarOptions,
  EventClickArg,
  EventInput,
  EventSourceFuncArg,
  EventSourceInput,
} from '@fullcalendar/core';
import { EventImpl } from '@fullcalendar/core/internal';
import dayGridPlugin from '@fullcalendar/daygrid';
import luxonPlugin from '@fullcalendar/luxon3'
import timeGridPlugin from '@fullcalendar/timegrid';
import googleCalendarPlugin from '@fullcalendar/google-calendar';
import interactionPlugin from '@fullcalendar/interaction';
import { InferInterceptors, Magics, XDataContext } from 'alpinejs';
import locales from '@fullcalendar/core/locales-all';

interface WireCalendar {
  create(start: Date, end: Date, allDay: boolean): void;

  fetchEvents(start: Date, end: Date): Promise<EventInput[]>;

  select(id: string): void;

  edit(
    id: string,
    start: Date | null,
    end: Date | null,
    allDay: boolean,
  ): Promise<void>;
}

interface FilamentCalendar extends Record<string, unknown> {
  calendar?: Calendar;

  googleCalendarEventSource(options: FilamentCalendarOptions): EventSourceInput;

  edit(event: EventImpl, revert: () => void): void;
}

type FilamentCalendarOptions = CalendarOptions & {
  googleCalendarApiKey?: string;
  googleCalendarId?: string;
};

type LivewireComponent<T, L> = T &
  XDataContext &
  ThisType<InferInterceptors<T> & XDataContext & Magics<T> & { $wire: L }>;

export default function calendar(
  options: FilamentCalendarOptions,
): LivewireComponent<FilamentCalendar, WireCalendar> {
  return {
    calendar: undefined,

    init() {
      const googleCalendar = this.googleCalendarEventSource(options);

      this.calendar = new Calendar(this.$root, {
        plugins: [
          dayGridPlugin,
          googleCalendarPlugin,
          interactionPlugin,
          luxonPlugin,
          timeGridPlugin,
        ],

        locales,
        locale: document.documentElement.lang,

        aspectRatio: 1.8,
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
        eventOrder: 'start,allDay,title',
        eventOrderStrict: true,
        eventTimeFormat: {
          hour: '2-digit',
          minute: '2-digit',
          meridiem: false,
        },
        dayMaxEventRows: true,
        nextDayThreshold: '06:00:00',

        selectable: true,

        ...options,

        eventSources: [
          googleCalendar,
          {
            events: (
              { start, end }: EventSourceFuncArg,
              resolve: (inputs: EventInput[]) => void,
              reject: (error: Error) => void,
            ): void => {
              this.$wire.fetchEvents(start, end).then(resolve).catch(reject);
            },
          },
        ],

        eventClick: ({
          event: { id, url, extendedProps },
          jsEvent: event,
        }: EventClickArg): Window | null | undefined => {
          event.preventDefault();

          if (url) {
            const isNotPlainLeftClick = (e: MouseEvent): boolean =>
              e.button > 0 || e.altKey || e.ctrlKey || e.metaKey || e.shiftKey;

            return window.open(
              url,
              extendedProps.shouldOpenUrlInNewTab || isNotPlainLeftClick(event)
                ? '_blank'
                : '_self',
            );
          }

          this.$wire.select(id);
        },

        eventDrop: ({ event, revert }) => {
          this.edit(event, revert);
        },

        eventResize: ({ event, revert }) => {
          this.edit(event, revert);
        },

        select: ({ start, end, allDay }) => {
          this.$wire.create(start, end, allDay);
        },
      });

      this.calendar.render();

      window.addEventListener('filament-calendar--refresh', () =>
        this.calendar?.refetchEvents(),
      );
    },

    googleCalendarEventSource(
      options: FilamentCalendarOptions,
    ): EventSourceInput {
      const { googleCalendarApiKey, googleCalendarId } = options;
      delete options.googleCalendarId;

      if (!googleCalendarApiKey) {
        return {};
      }

      return { googleCalendarId, className: 'fc-event-google' };
    },

    edit({ id, start, end, allDay }: EventImpl, revert: () => void): void {
      this.$wire.edit(id, start, end, allDay).catch(revert);
    },
  };
}
