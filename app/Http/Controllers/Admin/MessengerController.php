<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\QaTopicCreateRequest;
use App\Http\Requests\QaTopicReplyRequest;
use App\Models\QaMessage;
use App\Models\QaTopic;
use App\Models\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MessengerController extends Controller
{
    public function index()
    {
        $topics = QaTopic::where(function ($query) {
            $query
                ->where('creator_id', Auth::id())
                ->orWhere('receiver_id', Auth::id());
        })
            ->orderBy('created_at', 'DESC')
            ->get();


        $unreads = $this->unreadTopics();
        //dd($unreads);
        return Inertia::render('Admin/Messenger/Index', [
            'title' => 'All',
            'topics' => $topics->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'subject' => $topic->subject,
                    'created_at_human' => $topic->created_at->diffForHumans(),
                    'has_unreads' => $topic->hasUnreads(),
                    'receiver_or_creator' => [
                        'email' => optional($topic->receiverOrCreator())->email,
                    ],
                ];
            }),
            'unreads' => $unreads,

        ]);
    }

    public function createTopic()
    {
        $users = User::all()
            ->except(Auth::id());

        $unreads = $this->unreadTopics();
        return Inertia::render('Admin/Messenger/Create', [

            'users' => $users,
            'unreads' => $unreads,

        ]);
    }

    public function storeTopic(QaTopicCreateRequest $request)
    {
        $topic = QaTopic::create([
            'subject'     => $request->input('subject'),
            'creator_id'  => Auth::id(),
            'receiver_id' => $request->input('recipient'),
        ]);

        $topic->messages()->create([
            'sender_id' => Auth::id(),
            'content'   => $request->input('content'),
        ]);
        return redirect()->route('admin.messenger.index');
    }

    public function showMessages(QaTopic $topic)
    {
        $this->checkAccessRights($topic);
        $topic->load('creator:id,name,surname', 'receiver:id,name,surname');

        foreach ($topic->messages as $message) {
            if ($message->sender_id !== Auth::id() && $message->read_at === null) {
                $message->read_at = Carbon::now();
                $message->save();
            }
        }

        $unreads = $this->unreadTopics();
        //dd($topic);
        return Inertia::render('Admin/Messenger/Show', ['topic' => $topic, 'unreads' => $unreads]);
    }

    public function destroyTopic(QaTopic $topic)
    {
        $this->checkAccessRights($topic);

        $topic->delete();

        return redirect()->route('admin.messenger.index');
    }

    public function showInbox()
    {
        $topics = QaTopic::where(function ($query) {
            $query->where('receiver_id', Auth::id());
        })
            ->orderBy('created_at', 'DESC')
            ->get();


        $unreads = $this->unreadTopics();
        return Inertia::render('Admin/Messenger/Index', [
            'title' => 'Inbox',
            'topics' => $topics->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'subject' => $topic->subject,
                    'created_at_human' => $topic->created_at->diffForHumans(),
                    'has_unreads' => $topic->hasUnreads(),
                    'receiver_or_creator' => [
                        'email' => optional($topic->receiverOrCreator())->email,
                    ],
                ];
            }),
            'unreads' => $unreads,

        ]);
    }

    public function showOutbox()
    {
        $topics = QaTopic::where(function ($query) {
            $query
                ->where('creator_id', Auth::id());
        })
            ->orderBy('created_at', 'DESC')
            ->get();


        $unreads = $this->unreadTopics();

        return Inertia::render('Admin/Messenger/Index', [
            'title' => 'All',
            'topics' => $topics->map(function ($topic) {
                return [
                    'id' => $topic->id,
                    'subject' => $topic->subject,
                    'created_at_human' => $topic->created_at->diffForHumans(),
                    'has_unreads' => $topic->hasUnreads(),
                    'receiver_or_creator' => [
                        'email' => optional($topic->receiverOrCreator())->email,
                    ],
                ];
            }),
            'unreads' => $unreads,

        ]);
    }

    public function replyToTopic(QaTopicReplyRequest $request, QaTopic $topic)
    {
        $this->checkAccessRights($topic);

        $topic->messages()->create([
            'sender_id' => Auth::id(),
            'content'   => $request->input('content'),
        ]);

        return redirect()->route('admin.messenger.index');
    }

    public function showReply(QaTopic $topic)
    {
        $this->checkAccessRights($topic);

        $receiverOrCreator = $topic->receiverOrCreator();

        if ($receiverOrCreator === null || $receiverOrCreator->trashed()) {
            abort(404);
        }

        $unreads = $this->unreadTopics();

        return view('messenger.reply', compact('topic', 'unreads'));
    }



    public function unreadTopics(): array
    {
        $topics = QaTopic::where(function ($query) {
            $query
                ->where('creator_id', Auth::id())
                ->orWhere('receiver_id', Auth::id());
        })
            ->with('messages')
            ->orderBy('created_at', 'DESC')
            ->get();

        $inboxUnreadCount  = 0;
        $outboxUnreadCount = 0;

        foreach ($topics as $topic) {
            foreach ($topic->messages as $message) {
                if (
                    $message->sender_id !== Auth::id()
                    && $message->read_at === null
                ) {
                    if ($topic->creator_id !== Auth::id()) {
                        $inboxUnreadCount++;
                    } else {
                        $outboxUnreadCount++;
                    }
                }
            }
        }

        return [
            'inbox'  => $inboxUnreadCount,
            'outbox' => $outboxUnreadCount,
        ];
    }

    private function checkAccessRights(QaTopic $topic)
    {
        $user = Auth::user();

        if ($topic->creator_id !== $user->id && $topic->receiver_id !== $user->id) {
            return abort(401);
        }
    }
}
