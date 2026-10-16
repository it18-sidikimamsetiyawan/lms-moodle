<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Public verification by EJI Certificate ID.
 *
 * Reached as /local/ejicert/verify.php?id=EJI-CERT-2026-000001 or, through the
 * web-server rewrite, /certificate/verify/EJI-CERT-2026-000001.
 *
 * Uses the same permission as tool_certificate's own verification page
 * (tool/certificate:verify, granted to guests by default). IDs are sequential and
 * therefore guessable, so visitors who are not signed in see the recipient's name
 * masked; signed-in users see it in full. The QR code on the certificate uses
 * tool_certificate's random code and its own page, which is not guessable.
 *
 * @package    local_ejicert
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This file does not need require_login because capability to verify can be granted to guests.
// @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../config.php');

$certid = strtoupper(trim(optional_param('id', '', PARAM_ALPHANUMEXT)));

$PAGE->set_url(new moodle_url('/local/ejicert/verify.php', $certid ? ['id' => $certid] : []));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$heading = get_string('verifyheading', 'local_ejicert');
$PAGE->set_title($heading);
$PAGE->set_heading($heading);

if (!\tool_certificate\permission::can_verify()) {
    throw new moodle_exception('verifynotallowed', 'local_ejicert');
}

/**
 * Masks every word of a name after its first letter: "John Doe" -> "J*** D**".
 *
 * @param string $name
 * @return string
 */
function local_ejicert_mask_name(string $name): string {
    $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    return implode(' ', array_map(fn($w) => core_text::substr($w, 0, 1) . str_repeat('*', max(1, core_text::strlen($w) - 1)), $words));
}

echo $OUTPUT->header();
?>
<style>
  .ejicert-verify { max-width: 640px; margin: 0 auto; }
  .ejicert-verify .card { border-radius: 12px; overflow: hidden; }
  .ejicert-verify .ejicert-band { background: #152568; color: #fff; padding: 18px 24px; letter-spacing: .12em; font-weight: 700; font-size: .95rem; }
  .ejicert-verify dl { display: grid; grid-template-columns: minmax(120px, 34%) 1fr; gap: 10px 16px; margin: 0; }
  .ejicert-verify dt { color: #64748b; font-weight: 500; }
  .ejicert-verify dd { margin: 0; color: #0f172a; font-weight: 600; overflow-wrap: anywhere; }
  .ejicert-verify .ejicert-status { display: inline-block; padding: 2px 12px; border-radius: 999px; font-weight: 700; letter-spacing: .06em; }
  .ejicert-verify .ejicert-valid { background: #e6f4f1; color: #0b5f52; }
  .ejicert-verify .ejicert-expired { background: #fdf6e7; color: #6e4a14; }
  @media (max-width: 480px) { .ejicert-verify dl { grid-template-columns: 1fr; gap: 2px; } .ejicert-verify dd { margin-bottom: 10px; } }
</style>
<div class="ejicert-verify">
  <form method="get" action="<?php echo s($PAGE->url->out_omit_querystring()); ?>" class="card card-body mb-4">
    <label for="ejicert-id" class="form-label"><?php echo get_string('enterid', 'local_ejicert'); ?></label>
    <div class="d-flex flex-wrap gap-2">
      <input id="ejicert-id" name="id" class="form-control flex-grow-1" style="min-width: 0; flex-basis: 220px;"
             value="<?php echo s($certid); ?>" placeholder="EJI-CERT-2026-000001" autocomplete="off">
      <button class="btn btn-primary" type="submit"><?php echo get_string('verify', 'local_ejicert'); ?></button>
    </div>
  </form>
<?php
if ($certid !== '') {
    $issue = \local_ejicert\issuer::find_by_certid($certid);
    if (!$issue) {
        echo $OUTPUT->notification(get_string('notfound', 'local_ejicert'), \core\output\notification::NOTIFY_ERROR, false);
    } else {
        \tool_certificate\event\certificate_verified::create_from_issue($issue)->trigger();

        $data = json_decode($issue->data, true) ?: [];
        $name = $data['userfullname'] ?? '';
        if (!isloggedin() || isguestuser()) {
            $name = local_ejicert_mask_name($name);
        }
        $course = $data['coursefullname'] ?? $issue->templatename;
        $expired = $issue->expires && $issue->expires < time();
        $issuer = get_config('local_ejicert', 'issuername') ?: 'Eka Jaya Internasional';
        $rows = [
            get_string('certid', 'local_ejicert') => $issue->certid,
            get_string('student', 'local_ejicert') => $name,
            get_string('course', 'local_ejicert') => $course,
            get_string('completiondate', 'local_ejicert') => \local_ejicert\issuer::completion_date($issue),
            get_string('issuedon', 'local_ejicert') => userdate($issue->timecreated, get_string('strftimedate', 'langconfig')),
            get_string('issuedby', 'local_ejicert') => $issuer,
        ];
        echo '<div class="card"><div class="ejicert-band">' . s(core_text::strtoupper($heading)) . '</div><div class="card-body">';
        echo '<p class="mb-3">' . get_string('status', 'local_ejicert') . ': <span class="ejicert-status ' .
            ($expired ? 'ejicert-expired">' . get_string('status_expired', 'local_ejicert') :
                'ejicert-valid">' . get_string('status_valid', 'local_ejicert')) . '</span></p><dl>';
        foreach ($rows as $label => $value) {
            echo '<dt>' . s($label) . '</dt><dd>' . format_string($value) . '</dd>';
        }
        echo '</dl></div></div>';
    }
}
echo '</div>';
echo $OUTPUT->footer();
