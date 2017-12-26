<?php


namespace Armandsar\LaravelTranslationio;

use Armandsar\LaravelTranslationio\PrettyVarExport;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;

class SourceSaver
{
    /**
     * @var Application
     */
    private $application;
    /**
     * @var Filesystem
     */
    private $filesystem;
    /**
     * @var PrettyVarExport
     */
    private $prettyVarExport;

    public function __construct(
        Application $application,
        FileSystem $fileSystem,
        PrettyVarExport $prettyVarExport
    )
    {
        $this->application = $application;
        $this->filesystem = $fileSystem;
        $this->prettyVarExport = $prettyVarExport;
    }

    public function call($sourceEdit, $sourceLocale)
    {
        $dir = $this->localePath($sourceLocale);

        $this->makeDirectoryIfNotExisting($dir);

        $group = $this->group($sourceEdit['key']);
        $groupFile = $dir . DIRECTORY_SEPARATOR . $group . '.php';

        if ($this->filesystem->exists($groupFile)) {
            $translations = $this->filesystem->getRequire($groupFile);

            $translations = $this->applySourceEditInTranslations($translations, $sourceEdit);

            $fileContent = <<<'EOT'
<?php
return {{translations}};
EOT;

            $prettyTranslationsExport = $this->prettyVarExport->call($translations, ['array-align' => true]);
            $fileContent  = str_replace('{{translations}}', $prettyTranslationsExport, $fileContent);

            $this->filesystem->put($groupFile, $fileContent);
        }
    }


    private function localePath($locale)
    {
        return $this->path() . DIRECTORY_SEPARATOR . $locale;
    }

    private function path()
    {
        return $this->application['path.lang'];
    }

    private function makeDirectoryIfNotExisting($directory)
    {
        if ( ! $this->filesystem->exists($directory)) {
            $this->filesystem->makeDirectory($directory);
        }
    }

    private function group($key)
    {
        return explode('.', $key)[0];
    }

    private function keys($key)
    {
        $keyParts = explode('.', $key);
        array_shift($keyParts); // remove group part
        return $keyParts;
    }

    private function applySourceEditInTranslations($translations, $sourceEdit)
    {
        $keys = $this->keys($sourceEdit['key']);
        $oldText = $sourceEdit['old_text'];
        $newText = $sourceEdit['new_text'];

        $current = &$translations;

        for ($i = 0; $i < count($keys) - 1; $i++) {
            $key = $keys[$i];
            $current = &$current[$key];
        }

        if ($current[$keys[count($keys) - 1]] == $oldText) {
            $current[$keys[count($keys) - 1]] = $newText;
        }

        return $translations;
    }
}
