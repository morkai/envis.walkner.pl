<?php

include_once __DIR__ . '/../_common.php';

bad_request_if(empty($_GET['id']) || !is_numeric($_GET['id']));

$query = <<<SQL
SELECT i.id, i.owner, i.relatedFactory, i.relatedMachine
FROM issues i
WHERE i.id=?
LIMIT 1
SQL;

$issue = fetch_one($query, array(1 => $_GET['id']));

not_found_if(empty($issue));

$relatedIssues = array_map(function($issue) use($statuses)
{
  escape_vars($issue->subject);

  $issue->status = e($statuses[$issue->status]);
  $issue->percent = is_numeric($issue->percent) ? ((int)$issue->percent . '%') : '-';

  return $issue;
}, fetch_all("SELECT i.id, i.subject, i.status, i.percent FROM issues i WHERE i.id IN(SELECT r.issue2 FROM issue_relations r WHERE r.issue1=:issue)",
             array(':issue' => $issue->id)));

$currentUser = $_SESSION['user'];

$canAddLinks = $currentUser->isSuper() || $issue->owner == $currentUser->getId() || (!$issue->owner && is_allowed_to('service/edit'));

$docsViewer = is_issue_docs_viewer($currentUser, $issue);
$docsViewerSuffix = $docsViewer ? '&docs=1' : '';

?>
<div id=relatedIssues>
  <table>
    <thead>
      <tr>
        <th>ID
        <th>Temat
        <th>Status
        <th>% wykonania
    <? if ($canAddLinks): ?>
        <th>Akcje
    <tfoot>
      <tr>
        <td class="table-options" colspan=5>
          <a href="<?= url_for("service/?relate={$issue->id}") ?>">Dodaj nowe powiązanie</a>
    <? endif ?>
    <tbody>
      <? if (empty($relatedIssues)): ?>
      <tr>
        <td colspan=5>Brak powiązań.
      <? endif ?>
      <? foreach ($relatedIssues as $relatedIssue): ?>
      <tr>
        <td><?= $relatedIssue->id ?>
        <td class="clickable"><a href="<?= url_for("service/view.php?id={$relatedIssue->id}{$docsViewerSuffix}") ?>"><?= $relatedIssue->subject ?></a>
        <td><?= $relatedIssue->status ?>
        <td><?= $relatedIssue->percent ?>
        <? if ($canAddLinks): ?>
        <td class="actions">
          <ul>
            <li><?= fff('Usuń powiązanie', 'link_delete', "service/links/delete.php?issue1={$issue->id}&issue2={$relatedIssue->id}") ?>
          </ul>
        <? endif ?>
      <? endforeach ?>
  </table>
</div>
<script>
$(function()
{
  $('#relatedIssues table').makeClickable();
});
</script>
