/**
 * Returns a human-readable relative time string for a given UTC datetime string.
 * Server datetimes arrive without a timezone suffix but are stored in UTC;
 * we append 'Z' so the browser parses them as UTC, not local time.
 * @param {string} dt - datetime string from the server (e.g. "2026-07-25 10:30:00")
 * @returns {string}
 */
function timeAgo(dt) {
    if (!dt) return '';
    /* Normalise space separator and append 'Z' when no offset is present so
       the browser treats the value as UTC (matches how the server stores it). */
    var normalized = dt.replace(' ', 'T');
    if (!/Z|[+-]\d{2}:?\d{2}$/.test(normalized)) normalized += 'Z';
    var d = new Date(normalized);
    if (isNaN(d.getTime())) return '';
    var sec = Math.floor((Date.now() - d.getTime()) / 1000);
    if (sec < 60)    return 'just now';
    if (sec < 3600)  return Math.floor(sec / 60) + ' min ago';
    if (sec < 86400) return Math.floor(sec / 3600) + ' hr ago';
    var days = Math.floor(sec / 86400);
    if (days < 30)   return days + ' days ago';
    var months = Math.floor(days / 30);
    if (months < 12) return months === 1 ? '1 month ago' : months + ' months ago';
    var years = Math.floor(days / 365);
    return years === 1 ? 'a year ago' : years + ' years ago';
}
