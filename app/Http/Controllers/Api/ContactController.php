<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContactRequest;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    /**
     * GET /api/contacts — paginated list
     */
    public function index(): JsonResponse
    {
        $contacts = Contact::orderBy('created_at', 'desc')->paginate(15);

        return response()->json($contacts);
    }

    /**
     * POST /api/contacts — create a contact
     */
    public function store(StoreContactRequest $request): JsonResponse
    {
        $contact = Contact::create($request->validated());

        return response()->json($contact, 201);
    }

    /**
     * POST /api/contacts/{id}/unsubscribe — mark as unsubscribed
     */
    public function unsubscribe(int $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);

        $contact->update(['status' => 'unsubscribed']);

        return response()->json($contact);
    }
}
