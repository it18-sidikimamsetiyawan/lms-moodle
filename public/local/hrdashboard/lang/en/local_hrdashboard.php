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
 * English strings for local_hrdashboard.
 *
 * @package    local_hrdashboard
 * @copyright  2026 EJI ICT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'HR Learning Dashboard';
$string['privacy:metadata'] = 'The HR Learning Dashboard only aggregates data already stored by Moodle core and does not store personal data of its own.';
$string['hrdashboard:viewown'] = 'View own learning dashboard';
$string['hrdashboard:viewhr'] = 'View the HR learning dashboard (other users\' progress)';
$string['hrdashboard:export'] = 'Export HR learning reports';
$string['task:refreshstats'] = 'Refresh HR learning dashboard statistics';

// Navigation and titles.
$string['hrdashboard'] = 'HR Dashboard';
$string['hrdashboardtitle'] = 'HR Learning Dashboard';
$string['mylearning'] = 'My Learning';
$string['mylearningtitle'] = 'My Learning Dashboard';
$string['coursemonitoring'] = 'Course Monitoring';
$string['employeeprogress'] = 'Employee Progress';
$string['employeedetail'] = 'Employee detail';
$string['coursedetail'] = 'Course detail';
$string['backtodashboard'] = 'Back to HR Dashboard';
$string['openhrdashboard'] = 'Open HR Dashboard';

// Settings.
$string['settings:scope'] = 'Scope';
$string['settings:attributes'] = 'Employee attributes';
$string['settings:attributes_desc'] = 'Where Department, Division and Position come from. Choose a core user field or a custom profile field (text or menu). Attributes left on "None" are hidden from filters and tables. The HRIS sync can fill these fields later without any change to the dashboard.';
$string['settings:risk'] = 'At-risk rules';
$string['settings:risk_desc'] = 'An employee is "At Risk" in a course when any rule below matches. Set a value to 0 to switch that rule off. The reason is always shown to HR.';
$string['settings:performance'] = 'Performance and display';
$string['settings:links'] = '<a href="{$a->hr}">Open HR Dashboard</a> · <a href="{$a->my}">Open My Learning</a> · <a href="{$a->roles}">Manage roles</a> (grant <em>local/hrdashboard:viewhr</em> at system level for all employees or on a course category for a limited scope)';
$string['categoryscope'] = 'Course category scope';
$string['categoryscope_desc'] = 'Only courses in these categories (and their sub-categories) are monitored. Leave empty for every category.';
$string['employeesource'] = 'Employee population';
$string['employeesource_desc'] = 'Who counts as an employee for the HR KPIs. Site administrators, guests, deleted and service accounts are always excluded.';
$string['employeesource:all'] = 'All confirmed users';
$string['employeesource:enrolled'] = 'Users enrolled in a monitored course';
$string['employeesource:cohort'] = 'Members of a cohort';
$string['employeecohort'] = 'Employee cohort';
$string['employeecohort_desc'] = 'Used when the employee population is "Members of a cohort" (for example the cohort filled by the HRIS sync).';
$string['includehidden'] = 'Include hidden courses';
$string['includehidden_desc'] = 'Also monitor courses that are hidden from learners.';
$string['attr:department'] = 'Department';
$string['attr:division'] = 'Division';
$string['attr:position'] = 'Position';
$string['attrfield_desc'] = 'Source field for this attribute.';
$string['source:department'] = 'Core user field: Department';
$string['source:institution'] = 'Core user field: Institution';
$string['source:profile'] = 'Profile field';
$string['none'] = 'None';
$string['inactivedays'] = 'Inactive days';
$string['inactivedays_desc'] = 'Flag an unfinished course when the employee has had no activity in it for more than this many days.';
$string['nologindays'] = 'No-login days';
$string['nologindays_desc'] = 'Flag employees with unfinished courses who have not logged in for more than this many days. Also defines the "Active employees" window.';
$string['notstarteddays'] = 'Not-started days';
$string['notstarteddays_desc'] = 'Flag an enrolment that has not been started this many days after enrolment.';
$string['lowprogress'] = 'Low progress (%)';
$string['lowprogress_desc'] = 'Flag when progress is below this percentage and a due date (or the course end date) falls within the deadline window.';
$string['deadlinedays'] = 'Deadline window (days)';
$string['deadlinedays_desc'] = 'Number of days before a due date in which low progress becomes a risk.';
$string['pendingthreshold'] = 'Pending activities threshold';
$string['pendingthreshold_desc'] = 'Flag when the number of pending activities in a course reaches this value. 0 = off.';
$string['cachettl'] = 'Statistics cache (minutes)';
$string['cachettl_desc'] = 'How long aggregated HR statistics are reused before being rebuilt. 0 rebuilds on every page load (not recommended on large sites). HR can always force a rebuild with the Refresh button.';
$string['trendweeks'] = 'Completion trend (weeks)';
$string['trendweeks_desc'] = 'Number of weeks shown in the completion trend chart.';
$string['timelinedays'] = 'Timeline history (days)';
$string['timelinedays_desc'] = 'Days of activity shown in the employee timeline.';

// Statuses and risks.
$string['status:notstarted'] = 'Not started';
$string['status:inprogress'] = 'In progress';
$string['status:atrisk'] = 'At risk';
$string['status:overdue'] = 'Overdue';
$string['status:completed'] = 'Completed';
$string['risk:overdue'] = '{$a} overdue activity(ies)';
$string['risk:courseended'] = 'Course ended {$a} day(s) ago, not completed';
$string['risk:lowprogress'] = 'Progress {$a->progress}% with a deadline in {$a->days} day(s)';
$string['risk:inactive'] = 'No activity in this course for {$a} days';
$string['risk:notstarted'] = 'Enrolled {$a} days ago, not started';
$string['risk:nologin'] = 'No login for {$a} days';
$string['risk:neverloggedin'] = 'Never logged in ({$a} days since account creation)';
$string['risk:pending'] = '{$a} pending activities';

// KPIs.
$string['kpi:totalemployees'] = 'Total employees';
$string['kpi:activeemployees'] = 'Active employees';
$string['kpi:activewindow'] = 'logged in within {$a} days';
$string['kpi:withactivity'] = 'With learning activity';
$string['kpi:noactivity'] = 'No activity';
$string['kpi:enrolled'] = 'Enrolled employees';
$string['kpi:enrolments'] = '{$a} enrolments';
$string['kpi:completionrate'] = 'Course completion rate';
$string['kpi:completedof'] = '{$a->completed} of {$a->total} enrolments';
$string['kpi:avgprogress'] = 'Average progress';
$string['kpi:avgscore'] = 'Average score';
$string['kpi:overdue'] = 'Overdue activities';
$string['kpi:atrisk'] = 'Employees at risk';
$string['kpi:mycourses'] = 'My courses';
$string['kpi:completed'] = 'Completed';
$string['kpi:inprogress'] = 'In progress';
$string['kpi:notstarted'] = 'Not started';
$string['kpi:overallprogress'] = 'Overall progress';
$string['kpi:pending'] = 'Pending activities';
$string['kpi:overduecount'] = '{$a} overdue';
$string['kpi:tracked'] = 'Tracked activities';

// Sections.
$string['section:coursecompletion'] = 'Course completion overview';
$string['section:departmentprogress'] = '{$a} progress';
$string['section:statusdistribution'] = 'Employee status distribution';
$string['section:completiontrend'] = 'Completion trend';
$string['section:coursemonitoring'] = 'Course monitoring';
$string['section:atrisk'] = 'Employees at risk';
$string['section:recent'] = 'Recent learning activity';
$string['section:activities'] = 'Activities';
$string['section:employees'] = 'Employees';
$string['section:courses'] = 'Courses';
$string['section:summary'] = 'My learning summary';
$string['section:continue'] = 'Continue learning';
$string['section:pending'] = 'Pending activities';
$string['section:myprogress'] = 'My progress';
$string['section:timeline'] = 'Recent activity';

// Table headers.
$string['col:course'] = 'Course';
$string['col:category'] = 'Category';
$string['col:enrolled'] = 'Enrolled';
$string['col:notstarted'] = 'Not started';
$string['col:inprogress'] = 'In progress';
$string['col:atrisk'] = 'At risk';
$string['col:overdue'] = 'Overdue';
$string['col:completed'] = 'Completed';
$string['col:completionrate'] = 'Completion %';
$string['col:avgprogress'] = 'Avg progress';
$string['col:employee'] = 'Employee';
$string['col:progress'] = 'Progress';
$string['col:score'] = 'Score';
$string['col:lastactivity'] = 'Last activity';
$string['col:status'] = 'Status';
$string['col:activity'] = 'Activity';
$string['col:type'] = 'Type';
$string['col:due'] = 'Due date';
$string['col:action'] = 'Action';
$string['col:reasons'] = 'Reasons';
$string['col:courses'] = 'Courses';
$string['col:completedon'] = 'Completed on';
$string['col:grade'] = 'Grade';
$string['col:section'] = 'Section';
$string['col:completion'] = 'Completion';
$string['col:pending'] = 'Pending';
$string['col:enrolledon'] = 'Enrolled on';
$string['col:lastlogin'] = 'Last login';
$string['col:employees'] = 'Employees';
$string['col:when'] = 'When';

// Filters and actions.
$string['filters'] = 'Filters';
$string['filter:category'] = 'Category';
$string['filter:course'] = 'Course';
$string['filter:status'] = 'Status';
$string['filter:search'] = 'Search employee';
$string['filter:from'] = 'Activity from';
$string['filter:to'] = 'Activity to';
$string['filter:all'] = 'All';
$string['apply'] = 'Apply';
$string['reset'] = 'Reset';
$string['refresh'] = 'Refresh data';
$string['refreshed'] = 'Statistics rebuilt.';
$string['export'] = 'Export';
$string['export:csv'] = 'Export CSV';
$string['export:excel'] = 'Export Excel';
$string['export:pdf'] = 'Export PDF';
$string['exportfilename'] = 'employee-learning-progress';
$string['viewdetail'] = 'View detail';
$string['viewall'] = 'View all';
$string['viewcourse'] = 'View course';
$string['start'] = 'Start';
$string['continue'] = 'Continue';
$string['continuelearning'] = 'Continue learning';
$string['showing'] = 'Showing {$a->from}–{$a->to} of {$a->total}';

// Misc.
$string['never'] = 'Never';
$string['nodue'] = 'No due date';
$string['nodata'] = 'No data for the current selection.';
$string['nocourses'] = 'No monitored courses in your scope yet.';
$string['nocoursesyet'] = 'You are not enrolled in any course yet.';
$string['nopending'] = 'Nothing pending — everything is done.';
$string['notimeline'] = 'No learning activity in the last {$a} days.';
$string['noatrisk'] = 'No employee is at risk right now.';
$string['norecent'] = 'No completions recorded yet.';
$string['noemployees'] = 'No employees match the current filters.';
$string['generatedat'] = 'Data as of {$a}';
$string['activitiescompleted'] = '{$a->done} / {$a->total} activities';
$string['activitiescompletedlong'] = 'Completed activities: {$a->done} / {$a->total}';
$string['lastactivitylabel'] = 'Last activity';
$string['nextactivitylabel'] = 'Next activity';
$string['completionnotenabled'] = 'Completion tracking is not enabled for this course.';
$string['notracked'] = 'No activities with completion tracking.';
$string['streak'] = 'Learning streak';
$string['streakdays'] = '{$a} day(s) in a row';
$string['completedvspending'] = 'Completed vs pending activities';
$string['series:activities'] = 'Activities completed';
$string['series:courses'] = 'Courses completed';
$string['series:avgprogress'] = 'Average progress (%)';
$string['series:completionrate'] = 'Completion rate (%)';
$string['series:employees'] = 'Employees';
$string['unspecified'] = 'Unspecified';
$string['yesterday'] = 'Yesterday';
$string['timeline:completed'] = 'Completed {$a->type} – {$a->activity}';
$string['timeline:viewed'] = 'Opened {$a->type} – {$a->activity}';
$string['timeline:coursecompleted'] = 'Completed course – {$a}';
$string['recent:completed'] = '{$a->user} completed {$a->activity} in {$a->course}';
$string['gradehidden'] = 'Hidden';
$string['hiddencourse'] = 'Hidden course';
$string['notinscope'] = 'This employee or course is outside your HR scope.';
$string['state:complete'] = 'Completed';
$string['state:pass'] = 'Completed (passed)';
$string['state:fail'] = 'Completed (failed)';
$string['state:incomplete'] = 'Not completed';
$string['courseprogress'] = 'Course progress';
$string['employeecount'] = '{$a} employees';
$string['scopelimited'] = 'Your HR scope is limited to {$a} course category(ies).';
$string['employeesince'] = 'Account created {$a}';
$string['allcourses'] = 'All courses';
$string['chart:statuslabel'] = 'Enrolments';
$string['chart:week'] = 'Week of {$a}';

// UI modernisation (2026-10).
$string['subtitle:hr'] = 'Monitor employee learning performance across your scope.';
$string['subtitle:my'] = 'Here is your learning progress.';
$string['greeting:morning'] = 'Good morning, {$a} 👋';
$string['greeting:afternoon'] = 'Good afternoon, {$a} 👋';
$string['greeting:evening'] = 'Good evening, {$a} 👋';
$string['filters:more'] = 'More filters';
$string['filters:clearall'] = 'Clear all';
$string['filters:active'] = 'Filters';
$string['filters:remove'] = 'Remove filter: {$a}';
$string['filter:searchplaceholder'] = 'Search employee…';
$string['filter:allcategories'] = 'All categories';
$string['filter:allcourses'] = 'All courses';
$string['filter:allstatuses'] = 'All statuses';
$string['filter:daterange'] = 'Activity {$a->from} – {$a->to}';
$string['due:today'] = 'Due today';
$string['due:tomorrow'] = 'Due tomorrow';
$string['due:in'] = 'Due in {$a} days';
$string['due:on'] = 'Due {$a}';
$string['due:overdueby'] = 'Overdue by {$a} day(s)';
$string['due:none'] = 'No due date';
$string['empty:noatrisktitle'] = 'No employees at risk';
$string['empty:noatrisktext'] = 'All employees are currently on track.';
$string['empty:nopendingtitle'] = 'No pending activities';
$string['empty:nopendingtext'] = 'You\'re all caught up 🎉';
$string['empty:nodatatitle'] = 'No data for the selected filters';
$string['empty:nodatatext'] = 'Try changing or clearing your filters.';
$string['empty:norecenttitle'] = 'No recent completions';
$string['empty:norecenttext'] = 'Completed activities will appear here as employees learn.';
$string['empty:notimelinetitle'] = 'No recent activity';
$string['empty:notimelinetext'] = 'Your learning activity from the last {$a} days will appear here.';
$string['empty:nocoursestitle'] = 'No courses yet';
$string['empty:nocoursestext'] = 'Courses you are enrolled in will appear here.';
$string['empty:noemployeestitle'] = 'No employees match these filters';
$string['empty:noemployeestext'] = 'Try a different course, status or search term.';
$string['empty:allcompletedtitle'] = 'All courses completed';
$string['empty:allcompletedtext'] = 'Nothing left to continue — great work.';
$string['empty:noactivitiestitle'] = 'No tracked activities';
$string['empty:noactivitiestext'] = 'This course has no activities with completion tracking.';
$string['chart:completiondesc'] = 'Employees per status in each course';
$string['chart:statusdesc'] = 'All enrolments in scope, by status';
$string['chart:trenddesc'] = 'Activities and courses completed per week';
$string['chart:attributedesc'] = 'Average progress and completion rate per {$a}';
$string['section:coursemonitoringdesc'] = 'Completion and status breakdown per course';
$string['section:atriskdesc'] = 'Who needs attention, and why';
$string['section:recentdesc'] = 'Latest completed activities in your scope';
$string['section:continuedesc'] = 'Pick up where you left off';
$string['section:pendingdesc'] = 'Nearest due dates first';
$string['section:employeesdesc'] = '{$a} enrolments';
$string['section:activitiesdesc'] = 'Completion per activity among listed employees';
$string['col:breakdown'] = 'Breakdown';
$string['sortby'] = 'Sort by {$a}';
$string['viewprogress'] = 'View progress';
$string['andmore'] = '+{$a} more';
$string['hero:label'] = 'Overall learning progress';
$string['hero:activities'] = '<strong>{$a->done}</strong> of {$a->total} activities completed';
$string['hero:courses'] = '<strong>{$a->completed}</strong> of {$a->total} courses completed';
$string['hero:pending'] = '<strong>{$a}</strong> pending';
$string['streak:text'] = 'Keep learning daily to grow your streak';
$string['streak:none'] = 'Open any activity today to start a streak';
$string['kpi:completedcourses'] = 'Courses completed';
$string['kpi:inprogresscourses'] = 'In progress';
$string['scopeall'] = 'All categories';
$string['lastactivityshort'] = 'Last';
$string['nextactivityshort'] = 'Next';
$string['dueshort'] = 'Due';
$string['completedsummary'] = '{$a} completed';
$string['inprogresssummary'] = '{$a} in progress';
$string['atrisksummary'] = '{$a} at risk';
$string['overduesummary'] = '{$a} overdue';
$string['notstartedsummary'] = '{$a} not started';
$string['activitiessummary'] = '{$a} tracked activities';
$string['employeesinscope'] = '{$a} employees in scope';
