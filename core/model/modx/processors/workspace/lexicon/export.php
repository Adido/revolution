<?php
/**
 * Exports lexicon entries for a topic and namespace to a file.
 *
 * @param string $language The IANA code for the language. Defaults to en.
 * @param integer $topic The topic to export.
 * @param string $namespace The namespace to export.
 *
 * @package modx
 * @subpackage processors.workspace.lexicon.focus
 */
if (!$modx->hasPermission('lexicons')) return $modx->error->failure($modx->lexicon('permission_denied'));
$modx->lexicon->load('lexicon');

/* if downloading the file last exported */
if (!empty($_REQUEST['download'])) {
    $file = $_REQUEST['download'];
    $f = $modx->getOption('core_path').'export/lexicon/'.$file;

    if (!is_file($f)) return '';

    $o = file_get_contents($f);
    $bn = basename($file);

    header("Content-Type: application/force-download");
    header("Content-Disposition: attachment; filename=\"{$bn}\"");

    return $o;
}

/* verify inputs */
if (empty($_POST['namespace'])) return $modx->error->failure($modx->lexicon('namespace_err_ns'));
$namespace = $modx->getObject('modNamespace',is_numeric($_POST['namespace']) ? $_POST['namespace'] : array('name' => $_POST['namespace']));
if ($namespace == null) return $modx->error->failure($modx->lexicon('namespace_err_nf'));

if (empty($_POST['topic'])) return $modx->error->failure($modx->lexicon('topic_err_ns'));
$c = array(
    'namespace' => $_POST['namespace'],
);
if (is_numeric($_POST['topic'])) { $c['id'] = $_POST['topic']; }
  else { $c['name'] = $_POST['topic']; }
$topic = $modx->getObject('modLexiconTopic',$c);
if ($topic == null) return $modx->error->failure($modx->lexicon('topic_err_nf'));

if (empty($_POST['language'])) $_POST['language'] = 'en';

/* get all entries for topic, language and namespace */
$entries = $modx->getCollection('modLexiconEntry',array(
    'namespace' => $namespace->get('name'),
    'topic' => $topic->get('id'),
    'language' => $_POST['language'],
));

/* setup output content */
$o = "<?php
/*
 * @topic ".$topic->get('name')."
 * @namespace ".$namespace->get('name')."
 * @language ".$_POST['language']."
 */\n";
foreach ($entries as $entry) {
    $value = str_replace("'","\'",$entry->get('value'));
    $o .= "\$_lang['".$entry->get('name')."'] = '".$value."';\n";
}

/* setup filenames and write to file */
$file = $namespace->get('name').'/'.$topic->get('name').'.inc.php';
$fileName = $modx->getOption('core_path').'export/lexicon/'.$file;
$cacheManager = $modx->getCacheManager();
$s = $cacheManager->writeFile($fileName,$o);

/* output name to browser */
return $modx->error->success($file);