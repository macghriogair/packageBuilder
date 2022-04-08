<?php
declare(strict_types = 1);

namespace tools\packageBuilder\writer;

use tools\packageBuilder\util\ClassHolder;
use tools\packageBuilder\util\ClassHolderContainer;
use tools\packageBuilder\util\PackageContainer;

/**
 * Class PackageWriter - Writer for a single package.php file
 * @package tools\packageBuilder\writer
 */
class PackageWriter {

    private const HEADER = '<?php' . PHP_EOL . 'declare(strict_types = 1);' . PHP_EOL;
    /**
     * @var WriterOptions
     */
    private $options;
    /** @var string $packageNamespace */
    private $packageNamespace = null;
    /** @var array $dryRunResult */
    private $dryRunResult = [];

    public function __construct(WriterOptions $options) {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getDryRunResult(): array {
        return $this->dryRunResult;
    }

    /**
     * @param array $dryRunResult
     *
     * @return $this
     */
    public function setDryRunResult(array $dryRunResult): self {
        $this->dryRunResult = $dryRunResult;

        return $this;
    }

    /**
     * @param array $dryRunResult
     *
     * @return $this
     */
    public function addDryRunResult(array $dryRunResult): self {
        $this->dryRunResult[] = $dryRunResult;

        return $this;
    }

    /**
     * @return WriterOptions
     */
    public function getOptions(): WriterOptions {
        return $this->options;
    }

    /**
     * @return string
     */
    public function getPackageNamespace(): ?string {
        return $this->packageNamespace;
    }

    /**
     * @param string $packageNamespace
     *
     * @return $this
     */
    public function setPackageNamespace(?string $packageNamespace): self {
        $this->packageNamespace = $packageNamespace;

        return $this;
    }

    /**
     * Writes a package.php file
     *
     * @param ClassHolder $classHolder
     * @return string|null packagePath
     * @throws \Exception
     */
    public function writePackageFile(ClassHolder $classHolder): ?string {
        if ($classHolder->isEmpty()) {
            return null;
        }
        $packagePath = $classHolder->getPath() . '/package.php';
        $this->setPackageNamespace($classHolder->getNamespace());
        $content = $this->createContent($classHolder->getClasses());
        if ($this->getOptions()->isDryRun()) {
            $result[$packagePath] = $content;
            $this->addDryRunResult($result);
        } else {
            if (file_exists($packagePath) && !$this->getOptions()->doOverwriteExisting()) {
                echo 'Package file ' . $packagePath
                    . ' already exists and it could not be overwrite. Skipping...' . PHP_EOL;
                return null;
            }
            if (false === ($filehandler = fopen($packagePath, 'w'))) {
                throw new \Exception('Could not open ' . $packagePath);
            }
            fwrite($filehandler, $content);
            fclose($filehandler);
        }

        return $packagePath;
    }

    /**
     * Iterates over an ClassHolderContainer and creates package.php files
     *
     * @param ClassHolderContainer $container
     * @return PackageContainer
     * @throws \Exception
     */
    public function writePackageFiles(ClassHolderContainer $container): PackageContainer {
        $packageContainer = new PackageContainer();
        $this->setDryRunResult([]);
        if ($container->isEmpty()) {
            return $packageContainer;
        }
        /** @var ClassHolder $classHolder */
        foreach ($container as $classHolder) {
            if (null === ($packagePath = $this->writePackageFile($classHolder))) {
                continue;
            }

            $packageContainer->addPackageFile($this->getPackageNamespace(), $packagePath);
        }

        return $packageContainer;
    }

    /**
     * Creates the content of the package.php
     *
     * @param array $classes
     * @return string|null
     */
    private function createContent(array $classes): ?string {
        $packageNamespace = null;
        $content = self::HEADER . PHP_EOL;
        $content .= null === $this->getPackageNamespace()
            ? ''
            : 'namespace ' . $this->getPackageNamespace() . ';' . PHP_EOL . PHP_EOL;
        if ($this->getOptions()->isWithAutogeneratedTs()) {
            $content .= $this->getOptions()->getAutogeneratedTimestampInfoString() . PHP_EOL;
        }
        $content .= 'return [' . PHP_EOL;
        foreach ($classes as $class => $filename) {
            $content .= sprintf("    '%s' => '%s'," . PHP_EOL, $class, $filename);
        }
        $content .= '];';

        return $content;
    }
}