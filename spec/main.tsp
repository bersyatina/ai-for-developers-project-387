import "@typespec/http";
import "@typespec/openapi3";

using TypeSpec.Http;

/**
 * Календарь звонков — упрощённый аналог Cal.com.
 *
 * Две роли без авторизации: владелец календаря (предустановленный профиль,
 * админ-часть) и гость (бронирует слоты без аккаунта).
 *
 * Бизнес-правила:
 * - слоты по 30 минут, 24/7;
 * - окно записи — ближайшие 14 дней от текущей даты;
 * - на одно время нельзя две брони, даже для разных типов событий.
 */
@service(#{ title: "Call Calendar API" })
namespace CallCalendar;

/** Тип события, публикуемый владельцем календаря. */
model EventType {
  id: string;
  title: string;
  description: string;
  durationMinutes: int32;
}

/** Запрос на создание типа события (владелец). */
model CreateEventTypeRequest {
  title: string;

  description?: string;

  /** Длительность встречи в минутах. Сетка слотов — 30 минут. */
  durationMinutes: int32;
}

/** Свободный слот в выбранный день (окно 14 дней). */
model Slot {
  start: utcDateTime;
  end: utcDateTime;
  available: boolean;
}

/** Бронирование встречи (гость). */
model Booking {
  id: string;
  eventTypeId: string;
  start: utcDateTime;
  end: utcDateTime;
  guestName: string;
  guestEmail: string;
}

/** Запрос на создание брони (гость). start должен быть кратным 30 минутам. */
model CreateBookingRequest {
  eventTypeId: string;
  start: utcDateTime;
  guestName: string;
  guestEmail: string;
}

@error
model ErrorResponse {
  message: string;
  errors?: Record<unknown>;
}

/** Типы событий: публичный список для гостя и создание владельцем. */
@route("/api/event-types")
interface EventTypes {
  /** Список всех типов событий (гость). */
  @get
  list(): EventType[];

  /** Создать тип события (владелец). */
  @post
  create(@body body: CreateEventTypeRequest): CreatedResponse & EventType | ErrorResponse;
}

/** Свободные слоты выбранного типа события (гость). */
@route("/api/event-types/{eventTypeId}/slots")
interface Slots {
  /**
   * Свободные 30-минутные слоты на указанную дату (в пределах окна 14 дней).
   * Занятое время исключается — в ответе только свободные слоты.
   */
  @get
  list(@path eventTypeId: string, @query date: plainDate): Slot[];
}

/** Бронирования: создание гостем и список предстоящих встреч для владельца. */
@route("/api/bookings")
interface Bookings {
  /** Предстоящие встречи всех типов (владелец). */
  @get
  list(): Booking[];

  /** Записаться на свободный слот (гость). Конфликт времени — 409. */
  @post
  create(@body body: CreateBookingRequest): CreatedResponse & Booking | ErrorResponse;
}
