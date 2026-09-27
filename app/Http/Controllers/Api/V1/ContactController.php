<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Http\Requests\Api\V1\StoreContactRequest;
use App\Http\Requests\Api\V1\UpdateContactRequest;
use App\Http\Resources\ContactResource;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContactController extends Controller
{
    private const DEFAULT_PER_PAGE = 20;

    public function index(IndexContactRequest $request): AnonymousResourceCollection
    {
        $validated = $request->validated();
        $perPage = $validated['per_page'] ?? self::DEFAULT_PER_PAGE;

        $contacts = Contact::with(['category', 'tags'])
            ->search($validated)
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage);

        return ContactResource::collection($contacts);
    }

    public function show(Contact $contact): ContactResource
    {
        return new ContactResource($contact->load(['category', 'tags']));
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $contact = Contact::create($validated);
        $contact->tags()->attach($validated['tag_ids'] ?? []);

        return (new ContactResource($contact->load(['category', 'tags'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateContactRequest $request, Contact $contact): ContactResource
    {
        $validated = $request->validated();

        $contact->update($validated);
        $contact->tags()->sync($validated['tag_ids'] ?? []);

        return new ContactResource($contact->load(['category', 'tags']));
    }

    public function destroy(Contact $contact): JsonResponse
    {
        $contact->delete();

        return response()->json(null, 204);
    }
}
