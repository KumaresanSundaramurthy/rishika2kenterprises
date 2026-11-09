/**
 * Returns a human-readable relative time string for a given datetime string.
 * @param {string} dt - datetime string from the server (e.g. "2026-07-25 10:30:00")
 * @returns {string}
 */
function timeAgo(dt) {
    if (!dt) return '';
    var d = new Date(dt.replace(' ', 'T'));
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
