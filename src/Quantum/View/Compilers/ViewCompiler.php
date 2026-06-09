<?php

declare(strict_types=1);

namespace Quantum\View\Compilers;

use Quantum\View\Directives\DirectiveRegistry;

final class ViewCompiler
{
    public const VERSION = '0.3.0';
    private ?TemplateNodeCompiler $nodeCompiler = null;

    public function __construct(
        private readonly DirectiveRegistry $directives,
        private readonly ?TemplateSourceTokenizer $sourceTokenizer = null,
        private readonly ?TemplateTokenizer $tokenizer = null,
        private readonly ?TemplateParser $parser = null,
        private readonly ?TemplateBlockParser $blockParser = null,
    ) {}

    public function version(): string
    {
        return self::VERSION;
    }

    public function compileString(string $contents): string
    {
        $this->nodeCompiler()->reset();
        $result = '';

        foreach ($this->sourceTokenizer()->tokenize($contents) as $token) {
            if ($token->type() === TemplateSourceToken::INLINE_HTML) {
                $result .= $this->compileInlineHtml($token->value());
                continue;
            }

            $result .= $token->value();
        }

        $this->nodeCompiler()->assertBalanced();

        return $result;
    }

    private function compileInlineHtml(string $contents): string
    {
        $compiled = '';

        $nodes = $this->parser()->parse($this->tokenizer()->tokenize($contents));

        foreach ($this->blockParser()->parse($nodes) as $node) {
            $compiled .= $this->nodeCompiler()->compile($node);
        }

        return $compiled;
    }

    private function tokenizer(): TemplateTokenizer
    {
        return $this->tokenizer ?? new TemplateTokenizer();
    }

    private function sourceTokenizer(): TemplateSourceTokenizer
    {
        return $this->sourceTokenizer ?? new TemplateSourceTokenizer();
    }

    private function parser(): TemplateParser
    {
        return $this->parser ?? new TemplateParser();
    }

    private function blockParser(): TemplateBlockParser
    {
        return $this->blockParser ?? new TemplateBlockParser();
    }

    private function nodeCompiler(): TemplateNodeCompiler
    {
        return $this->nodeCompiler ??= new TemplateNodeCompiler($this->directives);
    }
}
