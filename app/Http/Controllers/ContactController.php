<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExportContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Services\ContactCsvExporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function index(): View
    {
        $categories = Category::all();
        $tags = Tag::all();

        return view('contact.index', compact('categories', 'tags'));
    }

    public function confirm(StoreContactRequest $request): View
    {
        $validated = $request->validated();
        $category = Category::find($validated['category_id']);
        $tags = Tag::whereIn('id', $validated['tag_ids'] ?? [])->get();

        return view('contact.confirm', compact('validated', 'category', 'tags'));
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $contact = Contact::create($validated);
        $contact->tags()->attach($validated['tag_ids'] ?? []);

        return redirect('/thanks');
    }

    public function thanks(): View
    {
        return view('contact.thanks');
    }

    public function export(ExportContactRequest $request, ContactCsvExporter $exporter): StreamedResponse
    {
        $query = Contact::with('category')
            ->search($request->validated())
            ->latest()
            ->orderByDesc('id');

        return $exporter->download($query, 'contacts_'.now()->format('YmdHis').'.csv');
    }
}
