<?php

use Opcodes\LogViewer\Services\AiExport\Providers\ChatGptProvider;

beforeEach(function () {
    $this->provider = new ChatGptProvider;
});

it('returns correct provider name', function () {
    expect($this->provider->getName())->toBe('ChatGPT');
});

it('returns svg icon', function () {
    expect($this->provider->getIcon())
        ->toContain('<svg')
        ->toContain('viewBox="0 0 24 24"');
});

it('is enabled by default', function () {
    expect($this->provider->isEnabled())->toBeTrue();
});

it('has correct character limit', function () {
    expect($this->provider->getMaxCharacterLimit())->toBe(6950);
});

it('generates correct URL with markdown content', function () {
    $markdown = "## Test Error\n\nThis is a test error message.";
    $url = $this->provider->generateUrl($markdown);

    expect($url)
        ->toStartWith('https://chat.openai.com/?q=')
        ->toContain(urlencode('## Test Error'));
});

it('truncates content when exceeding character limit', function () {
    $longContent = str_repeat('This is a very long error message. ', 300);
    $url = $this->provider->generateUrl($longContent);

    $parts = parse_url($url);
    parse_str($parts['query'] ?? '', $query);
    $content = $query['q'] ?? '';

    expect(strlen($content))
        ->toBeLessThan(8100)
        ->and($content)
        ->toContain('[Content truncated due to length limits]');
});

it('preserves markdown structure when truncating', function () {
    $markdown = "## Header\n\n### Subheader\n\n".str_repeat('Content ', 2000);
    $url = $this->provider->generateUrl($markdown);

    $parts = parse_url($url);
    parse_str($parts['query'] ?? '', $query);
    $content = $query['q'] ?? '';

    expect($content)
        ->toContain('## Header')
        ->toContain('### Subheader');
});

it('returns configuration array', function () {
    $config = $this->provider->getConfig();

    expect($config)
        ->toBeArray()
        ->toHaveKeys(['enabled', 'url', 'max_characters']);
});
