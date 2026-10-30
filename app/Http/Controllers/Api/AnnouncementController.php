<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Models\Announcement;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $this->authorize('viewAny', Announcement::class);

        $perPage = min(max((int) $request->input('per_page', 12), 1), 30);
        $announcements = Announcement::query()
            ->with('createdBy:id,name')
            ->latest()
            ->paginate($perPage);

        return $this->ok('Announcements retrieved successfully', $announcements);
    }

    public function store(StoreAnnouncementRequest $request)
    {
        $this->authorize('create', Announcement::class);

        $data = $request->safe()->except('media');

        if ($request->hasFile('media')) {
            $media = $request->file('media');
            $data['media_path'] = $media->store('announcements/'.now()->format('Y/m'), 'public');
            $data['media_type'] = $media->getMimeType();
        }

        $announcement = Announcement::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        return $this->created('Announcement published successfully', $announcement->load('createdBy:id,name'));
    }

    public function update(StoreAnnouncementRequest $request, Announcement $announcement)
    {
        $this->authorize('update', $announcement);

        $data = $request->safe()->except('media');
        $previousMediaPath = $announcement->media_path;

        if ($request->hasFile('media')) {
            $media = $request->file('media');
            $data['media_path'] = $media->store('announcements/'.now()->format('Y/m'), 'public');
            $data['media_type'] = $media->getMimeType();
        }

        $announcement->update($data);

        if ($request->hasFile('media') && $previousMediaPath) {
            Storage::disk('public')->delete($previousMediaPath);
        }

        return $this->ok('Announcement updated successfully', $announcement->fresh()->load('createdBy:id,name'));
    }

    public function destroy(Request $request, Announcement $announcement)
    {
        $this->authorize('delete', $announcement);

        $mediaPath = $announcement->media_path;
        $announcement->delete();

        if ($mediaPath) {
            Storage::disk('public')->delete($mediaPath);
        }

        return $this->ok('Announcement deleted successfully');
    }
}
