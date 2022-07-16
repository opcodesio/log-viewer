#!/usr/bin/env php
<?php

function ask(string $question, string $default = ''): string
{
    $answer = readline($question.($default ? " ({$default})" : null).': ');

    if (! $answer) {
        return $default;
    }

    return $answer;
}

function confirm(string $question, bool $default = false): bool
{
    $answer = ask($question.' ('.($default ? 'Y/n' : 'y/N').')');

    if (! $answer) {
        return $default;
    }

    return strtolower($answer) === 'y';
}

function writeln(string $line): void
{
    echo $line.PHP_EOL;
}

function run(string $command): string
{
    return trim((string) shell_exec($command));
}

function str_after(string $subject, string $search): string
{
    $pos = strrpos($subject, $search);

    if ($pos === false) {
        return $subject;
    }

    return substr($subject, $pos + strlen($search));
}

function slugify(string $subject): string
{
    return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $subject), '-'));
}

function title_case(string $subject): string
{
    return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $subject)));
}

function title_snake(string $subject, string $replace = '_'): string
{
    return str_replace(['-', '_'], $replace, $subject);
}

function replace_in_file(string $file, array $replacements): void
{
    $contents = file_get_contents($file);

    file_put_contents(
        $file,
        str_replace(
            array_keys($replacements),
            array_values($replacements),
            $contents
        )
    );
}

function remove_prefix(string $prefix, string $content): string
{
    if (str_starts_with($content, $prefix)) {
        return substr($content, strlen($prefix));
    }

    return $content;
}

function remove_composer_deps(array $names)
{
    $data = json_decode(file_get_contents(__DIR__.'/composer.json'), true);

    foreach ($data['require-dev'] as $name => $version) {
        if (in_array($name, $names, true)) {
            unset($data['require-dev'][$name]);
        }
    }

    file_put_contents(__DIR__.'/composer.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function remove_composer_script($scriptName)
{
    $data = json_decode(file_get_contents(__DIR__.'/composer.json'), true);

    foreach ($data['scripts'] as $name => $script) {
        if ($scriptName === $name) {
            unset($data['scripts'][$name]);
            break;
        }
    }

    file_put_contents(__DIR__.'/composer.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
}

function remove_readme_paragraphs(string $file): void
{
    $contents = file_get_contents($file);

    file_put_contents(
        $file,
        preg_replace('/<!--delete-->.*<!--\/delete-->/s', '', $contents) ?: $contents
    );
}

function safeUnlink(string $filename)
{
    if (file_exists($filename) && is_file($filename)) {
        unlink($filename);
    }
}

function determineSeparator(string $path): string
{
    return str_replace('/', DIRECTORY_SEPARATOR, $path);
}

function replaceForWindows(): array
{
    return preg_split('/\\r\\n|\\r|\\n/', run('dir /S /B * | findstr /v /i .git\ | findstr /v /i vendor | findstr /v /i '.basename(__FILE__).' | findstr /r /i /M /F:/ ":author :vendor :package VendorName skeleton migration_table_name vendor_name vendor_slug author@domain.com"'));
}

function replaceForAllOtherOSes(): array
{
    return explode(PHP_EOL, run('grep -E -r -l -i ":author|:vendor|:package|VendorName|skeleton|migration_table_name|vendor_name|vendor_slug|author@domain.com" --exclude-dir=vendor ./* ./.github/* | grep -v '.basename(__FILE__)));
}

$gitName = run('git config user.name');
$authorName = ask('Author name', $gitName);

$gitEmail = run('git config user.email');
$authorEmail = ask('Author email', $gitEmail);

$usernameGuess = explode(':', run('git config remote.origin.url'))[1];
$usernameGuess = dirname($usernameGuess);
$usernameGuess = basename($usernameGuess);
$authorUsername = ask('Author username', $usernameGuess);

$vendorName = ask('Vendor name', $authorUsername);
$vendorSlug = slugify($vendorName);
$vendorNamespace = ucwords($vendorName);
$vendorNamespace = ask('Vendor namespace', $vendorNamespace);

$currentDirectory = getcwd();
$folderName = basename($currentDirectory);

$packageName = ask('Package name', $folderName);
$packageSlug = slugify($packageName);
$packageSlugWithoutPrefix = remove_prefix('laravel-', $packageSlug);

$className = title_case($packageName);
$className = ask('Class name', $className);
$variableName = lcfirst($className);
$description = ask('Package description', "This is my package {$packageSlug}");

$usePhpStan = confirm('Enable PhpStan?', true);
$usePhpCsFixer = confirm('Enable PhpCsFixer?', true);
$useDependabot = confirm('Enable Dependabot?', true);
$useLaravelRay = confirm('Use Ray for debugging?', true);
$useUpdateChangelogWorkflow = confirm('Use automatic changelog updater workflow?', true);

writeln('------');
writeln("Author     : {$authorName} ({$authorUsername}, {$authorEmail})");
writeln("Vendor     : {$vendorName} ({$vendorSlug})");
writeln("Package    : {$packageSlug} <{$description}>");
writeln("Namespace  : {$vendorNamespace}\\{$className}");
writeln("Class name : {$className}");
writeln('---');
writeln('Packages & Utilities');
writeln('Use PhpCsFixer       : '.($usePhpCsFixer ? 'yes' : 'no'));
writeln('Use Larastan/PhpStan : '.($usePhpStan ? 'yes' : 'no'));
writeln('Use Dependabot       : '.($useDependabot ? 'yes' : 'no'));
writeln('Use Ray App          : '.($useLaravelRay ? 'yes' : 'no'));
writeln('Use Auto-Changelog   : '.($useUpdateChangelogWorkflow ? 'yes' : 'no'));
writeln('------');

writeln('This script will replace the above values in all relevant files in the project directory.');

if (! confirm('Modify files?', true)) {
    exit(1);
}

$files = (str_starts_with(strtoupper(PHP_OS), 'WIN') ? replaceForWindows() : replaceForAllOtherOSes());

foreach ($files as $file) {
    replace_in_file($file, [
        ':author_name' => $authorName,
        ':author_username' => $authorUsername,
        'author@domain.com' => $authorEmail,
        ':vendor_name' => $vendorName,
        ':vendor_slug' => $vendorSlug,
        'VendorName' => $vendorNamespace,
        ':package_name' => $packageName,
        ':package_slug' => $packageSlug,
        ':package_slug_without_prefix' => $packageSlugWithoutPrefix,
        'Skeleton' => $className,
        'skeleton' => $packageSlug,
        'migration_table_name' => title_snake($packageSlug),
        'variable' => $variableName,
        ':package_description' => $description,
    ]);

    match (true) {
        str_contains($file, determineSeparator('src/Skeleton.php')) => rename($file, determineSeparator('./src/'.$className.'.php')),
        str_contains($file, determineSeparator('src/SkeletonServiceProvider.php')) => rename($file, determineSeparator('./src/'.$className.'ServiceProvider.php')),
        str_contains($file, determineSeparator('src/Facades/Skeleton.php')) => rename($file, determineSeparator('./src/Facades/'.$className.'.php')),
        str_contains($file, determineSeparator('src/Commands/SkeletonCommand.php')) => rename($file, determineSeparator('./src/Commands/'.$className.'Command.php')),
        str_contains($file, determineSeparator('database/migrations/create_skeleton_table.php.stub')) => rename($file, determineSeparator('./database/migrations/create_'.title_snake($packageSlugWithoutPrefix).'_table.php.stub')),
        str_contains($file, determineSeparator('config/skeleton.php')) => rename($file, determineSeparator('./config/'.$packageSlugWithoutPrefix.'.php')),
        str_contains($file, 'README.md') => remove_readme_paragraphs($file),
        default => [],
    };
}

if (! $usePhpCsFixer) {
    safeUnlink(__DIR__.'/.php_cs.dist.php');
    safeUnlink(__DIR__.'/.github/workflows/php-cs-fixer.yml');
}

if (! $usePhpStan) {
    safeUnlink(__DIR__.'/phpstan.neon.dist');
    safeUnlink(__DIR__.'/phpstan-baseline.neon');
    safeUnlink(__DIR__.'/.github/workflows/phpstan.yml');

    remove_composer_deps([
        'phpstan/extension-installer',
        'phpstan/phpstan-deprecation-rules',
        'phpstan/phpstan-phpunit',
        'nunomaduro/larastan',
    ]);

    remove_composer_script('phpstan');
}

if (! $useDependabot) {
    safeUnlink(__DIR__.'/.github/dependabot.yml');
    safeUnlink(__DIR__.'/.github/workflows/dependabot-auto-merge.yml');
}

if (! $useLaravelRay) {
    remove_composer_deps(['spatie/laravel-ray']);
}

if (! $useUpdateChangelogWorkflow) {
    safeUnlink(__DIR__.'/.github/workflows/update-changelog.yml');
}

confirm('Execute `composer install` and run tests?') && run('composer install && composer test');

confirm('Let this script delete itself?', true) && unlink(__FILE__);
