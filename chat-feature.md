# TASK: Implement Offline LAN-Based Real-Time Chat Module

## Context

This is an existing Laravel application already running in production.

Environment:

- Laravel 11+   
- Bootstrap
- PostgreSQL
- Multiple users access the application through a local network (LAN)
- Application is hosted on a single server PC
- Users access via IP address
- No internet connection is available
- No cloud services are allowed
- No Firebase
- No Pusher
- No SaaS services
- No external APIs

The implementation MUST be completely self-hosted.

---

# CRITICAL REQUIREMENTS

1. DO NOT modify any existing business logic.
2. DO NOT change existing routes unless required.
3. DO NOT modify existing database tables.
4. DO NOT rename existing models.
5. DO NOT alter existing permissions.
6. DO NOT remove any package.
7. DO NOT change application theme/layout.
8. DO NOT break Filament functionality.
9. All new functionality must be isolated.
10. Follow SOLID principles.
11. Follow Laravel best practices.
12. Generate migration rollback support.
13. All code must be production-ready.
14. Use transactions where necessary.
15. Prevent N+1 queries.
16. Use eager loading.
17. Add validation everywhere.
18. Add authorization checks.
19. No placeholder code.
20. No TODO comments.

---

# FEATURE REQUIREMENT

Implement an internal real-time chat system.

Supported features:

- One-to-One Chat
- Group Chat
- Real-Time Messaging
- Unread Message Count
- Read Receipts
- Online Status
- Typing Indicator
- Notification Badge
- Message Search
- File Attachments
- Conversation List
- Recent Chat Ordering

No voice calls.

No video calls.

---

# REAL-TIME REQUIREMENT

Use:

Laravel Reverb

Requirements:

- Install and configure Laravel Reverb
- Configure broadcasting
- Configure private channels
- Configure presence channels
- Configure user online status
- Configure typing events
- Configure message delivery events

Do not use:

- Pusher
- Firebase
- Ably
- Soketi
- Any cloud service

---

# DATABASE STRUCTURE

Create new migrations only.

## conversations

id
type (private/group)
name nullable
created_by
created_at
updated_at

## conversation_participants

id
conversation_id
user_id
last_read_message_id nullable
joined_at

## messages

id
conversation_id
sender_id
message nullable
attachment_path nullable
attachment_name nullable
attachment_size nullable
created_at
updated_at

## message_reads

id
message_id
user_id
read_at

---

# MODEL REQUIREMENTS

Create:

- Conversation
- ConversationParticipant
- Message
- MessageRead

Relationships must be defined.

Use:

- belongsTo
- hasMany
- belongsToMany

where appropriate.

---

# AUTHORIZATION

Only participants of a conversation may:

- View messages
- Send messages
- Upload attachments
- Read messages

Unauthorized users must receive:

403 Forbidden

---

# ATTACHMENTS

Supported:

- pdf
- xlsx
- xls
- doc
- docx
- jpg
- jpeg
- png

Maximum:

20MB

Store using Laravel Storage.

Do not expose physical paths.

Generate secure download URLs.

---

# EVENTS

Create:

MessageSent
MessageRead
UserTyping
UserStoppedTyping

Broadcast events.

Use private channels.

---

# ONLINE STATUS

Use Presence Channels.

Show:

- Online
- Offline
- Last Seen

Store last activity timestamp.

---

# UNREAD COUNTER

Requirements:

- Conversation-wise unread count
- Global unread count
- Auto update without refresh

Must use broadcasting.

No polling.

---

# SEARCH

Allow searching:

- Message content
- Conversation name
- Participant name

Must be database indexed.

---

# LIVEWIRE UI

Create isolated module.

Suggested namespace:

App\Livewire\Chat

Components:

- ChatSidebar
- ConversationList
- ConversationView
- MessageComposer
- NotificationBell

Requirements:

- Mobile responsive
- Desktop responsive
- Tailwind styling
- Reusable components

Do not modify existing layouts.

Create dedicated chat views.

---

# INTEGRATION

Add a navigation item:

"Chat"

Requirements:

- Visible only to authenticated users
- Display unread badge
- Do not affect existing resources

---

# PERFORMANCE REQUIREMENTS

Conversation list:

- Paginated

Messages:

- Paginated
- Load latest 50
- Infinite scroll for history

Use eager loading.

Avoid N+1 queries.

---

# ERROR HANDLING

Handle:

- Invalid conversation
- Missing attachment
- Unauthorized access
- Deleted users
- Connection interruption

Provide graceful user feedback.

No application crashes.

---

# TESTING REQUIREMENTS

Create tests for:

- Message sending
- Message reading
- Authorization
- File uploads
- Presence channels
- Reverb broadcasting

Use Pest or PHPUnit depending on existing project.

Coverage should focus on critical paths.

---

# DELIVERABLES

Provide:

1. Migration files
2. Models
3. Policies
4. Events
5. Listeners
6. Livewire components
7. Filament integration
8. Routes
9. Controllers (if required)
10. Tests
11. Reverb configuration
12. Installation commands

---

# IMPLEMENTATION STRATEGY

Before changing anything:

1. Analyze existing application structure.
2. Identify user model.
3. Identify authentication mechanism.
4. Identify existing broadcasting setup.
5. Identify storage configuration.
6. Generate implementation plan.
7. Present plan first.
8. Wait for approval.
9. Only then start coding.

Never directly modify files before presenting the plan.

All changes must be incremental and reversible.