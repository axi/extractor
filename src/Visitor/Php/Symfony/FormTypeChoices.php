<?php

/*
 * This file is part of the PHP Translation package.
 *
 * (c) PHP Translation team <tobias.nyholm@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Translation\Extractor\Visitor\Php\Symfony;

use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use Translation\Extractor\Model\SourceLocation;
use Translation\Extractor\Visitor\Php\BasePHPVisitor;

class FormTypeChoices extends BasePHPVisitor implements NodeVisitor
{
    /**
     * @var int defaults to major version 3
     */
    protected $symfonyMajorVersion = 3;

    /**
     * @param int $sfMajorVersion
     */
    public function setSymfonyMajorVersion($sfMajorVersion)
    {
        $this->symfonyMajorVersion = $sfMajorVersion;
    }

    public function enterNode(Node $node)
    {
        // only Traverse *Type
        if ($node instanceof Stmt\Class_) {
            if (substr($node->name, -4) !== 'Type') {
                return NodeTraverser::DONT_TRAVERSE_CHILDREN;
            }
        }

        // symfony 3 displays key by default, where symfony 2 displays value
        $useKey = $this->symfonyMajorVersion == 3;

        // remember choices in this node
        $choicesNodes = [];

        // loop through array
        if ($node instanceof Node\Expr\Array_) {
            foreach ($node->items as $item) {
                if (!$item->key instanceof Node\Scalar\String_) {
                    continue;
                }

                if ($item->key->value === 'choices_as_values') {
                    $useKey = true;
                    continue;
                }

                if ($item->key->value !== 'choices') {
                    continue;
                }

                if (!$item->value instanceof Node\Expr\Array_) {
                    continue;
                }

                $choicesNodes[] = $item->value;
            }

            if (count($choicesNodes) > 0) {
                // probably will be only 1, but who knows
                foreach ($choicesNodes as $choices) {
                    // TODO: do something with grouped (multi-dimensional) arrays here
                    if (!$choices instanceof Node\Expr\Array_) {
                        continue;
                    }

                    foreach ($choices as $citem) {
                        $labelNode = $useKey ? $citem[0]->key : $citem[0]->value;
                        if (!$labelNode instanceof Node\Scalar\String_) {
                            continue;
                        }

                        $sl = new SourceLocation($labelNode->value, $this->getAbsoluteFilePath(), $choices->getAttribute('startLine'));
                        $this->collection->addLocation($sl);
                    }
                }
            }
        }
    }

    public function leaveNode(Node $node)
    {
    }

    public function beforeTraverse(array $nodes)
    {
    }

    public function afterTraverse(array $nodes)
    {
    }
}