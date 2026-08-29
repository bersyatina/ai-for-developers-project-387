<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\BookingService;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookings) {}

    /** Предстоящие встречи всех типов (владелец). */
    public function index()
    {
        $bookings = Booking::with('eventType')
            ->where('start', '>=', now())
            ->orderBy('start')
            ->get();

        return BookingResource::collection($bookings);
    }

    /** Записаться на свободный слот (гость). */
    public function store(StoreBookingRequest $request)
    {
        $booking = $this->bookings->create($request->validated());

        return response()->json(new BookingResource($booking), 201);
    }
}
