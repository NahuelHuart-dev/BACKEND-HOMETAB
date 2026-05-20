# Final Presentation Documentation

## Product identity

**HomeTab** is a digital tool for managing shared households. It solves common coexistence problems: unclear chores, scattered expenses, missing reminders, lack of shared calendar visibility and fragmented communication.

## Proposed solution

HomeTab provides:

- Household creation and invitation codes.
- Member management.
- Assignable tasks.
- Shared calendar events.
- Shared and individual expenses.
- Balance and settlement calculations.
- Household chat.
- User profile with avatar or photograph.
- AI assistant for querying household data in natural language.
- Private superadmin backoffice.

## What was developed

The project includes:

| Area | Description |
| --- | --- |
| Vue frontend | Main user experience: landing, login, registration, household hub and functional tabs. |
| Symfony backend | JSON API, authentication, persistence, admin panel, internal tools and protected documentation. |
| Landing page | Complementary public material for the final presentation. |

## Digitization Module

The digitization module is the HomeTab AI assistant, exposed through:

```text
POST /api/assistant/chat
```

It lets authenticated users ask natural-language questions about their household data:

- Pending tasks.
- Upcoming events.
- Pending shared payments.
- Balances.
- General household summary.

The current implementation is read-only. It does not modify data and only works with households the user belongs to.
