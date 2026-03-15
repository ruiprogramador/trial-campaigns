<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddContactToListRequest;
use App\Http\Requests\StoreContactListRequest;
use App\Models\ContactList;
use Illuminate\Http\JsonResponse;

class ContactListController extends Controller
{
    /**
     * GET /api/contact-lists — list all
     */
    public function index(): JsonResponse
    {
        $lists = ContactList::withCount('contacts')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($lists);
    }

    /**
     * POST /api/contact-lists — create
     */
    public function store(StoreContactListRequest $request): JsonResponse
    {
        $list = ContactList::create($request->validated());

        return response()->json($list, 201);
    }

    /**
     * POST /api/contact-lists/{id}/contacts — add a contact to a list
     */
    public function addContact(AddContactToListRequest $request, int $id): JsonResponse
    {
        $list = ContactList::findOrFail($id);

        // syncWithoutDetaching prevents duplicates (Fix #8 also enforced at DB level)
        $list->contacts()->syncWithoutDetaching([$request->validated('contact_id')]);

        return response()->json(['message' => 'Contact added to list.']);
    }
}
