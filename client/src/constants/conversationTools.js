export const CONVERSATION_STARTERS = [
  "What interested you in this event?",
  "What kind of work are you currently doing?",
  "Which session are you looking forward to?",
  "Would you prefer to chat here before meeting?",
  "Would a five-minute introduction at the event work for you?",
];

export const AVAILABILITY_STATUSES = [
  { value: "available", label: "Available to chat" },
  { value: "messages_welcome", label: "Messages welcome" },
  { value: "prefer_later", label: "Prefer to message later" },
  { value: "observing", label: "Attending but observing" },
  { value: "unavailable", label: "Not currently available" },
];

export const CONVERSATION_BOUNDARIES = [
  { key: "text_only", label: "Text only" },
  { key: "no_video_calls", label: "No video calls" },
  { key: "one_message_at_a_time", label: "One message at a time" },
  { key: "event_only_meeting", label: "Meeting only at the event" },
  { key: "no_spontaneous_calls", label: "No spontaneous calls" },
  { key: "ask_before_groups", label: "Ask before adding to groups" },
];

export function availabilityLabel(value) {
  return AVAILABILITY_STATUSES.find((status) => status.value === value)?.label ?? "";
}
