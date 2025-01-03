<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Type\Map;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Mutation;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\UriValue;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Directive;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationMode;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\SourceScheme;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\MutationCollection;

return Map::fromEntries([

    Scope::backend(),

    new MutationCollection(
        new Mutation(
            MutationMode::Extend,
            Directive::StyleSrcElem,
            SourceScheme::data,
            new UriValue('fonts.googleapis.com')
        ),
        new Mutation(
            MutationMode::Extend,
            Directive::FontSrc,
            SourceScheme::data,
            new UriValue('fonts.gstatic.com')
        ),
    ),
]);
