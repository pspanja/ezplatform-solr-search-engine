<?php

/**
 * This file is part of the eZ Platform Solr Search Engine package.
 *
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 *
 * @version //autogentag//
 */
namespace EzSystems\EzPlatformSolrSearchEngine\CoreFilter;

use EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion\DocumentTypeId;
use EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion\LanguageCode;
use EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion\IndexedMainTranslation;
use EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion\IndexedMainTranslationCore;
use EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion\IndexedAlwaysAvailable;
use EzSystems\EzPlatformSolrSearchEngine\API\Query\Criterion\IndexedLanguageCode;
use EzSystems\EzPlatformSolrSearchEngine\CoreFilter;
use EzSystems\EzPlatformSolrSearchEngine\EndpointResolver;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalAnd;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalNot;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\LogicalOr;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\Operator;

/**
 * Native core filter handles:.
 *
 * - search type (Content and Location)
 * - prioritized languages fallback
 * - always available language fallback
 * - main language search
 */
class NativeCoreFilter extends CoreFilter
{
    /**
     * @var \EzSystems\EzPlatformSolrSearchEngine\EndpointResolver
     */
    private $endpointResolver;

    /**
     * @param \EzSystems\EzPlatformSolrSearchEngine\EndpointResolver $endpointResolver
     */
    public function __construct(EndpointResolver $endpointResolver)
    {
        $this->endpointResolver = $endpointResolver;
    }

    public function apply(Query $query, array $languageSettings, $documentTypeIdentifier)
    {
        $languages = (
            empty($languageSettings['languages']) ?
                array() :
                $languageSettings['languages']
        );
        $useAlwaysAvailable = (
            !isset($languageSettings['useAlwaysAvailable']) ||
            $languageSettings['useAlwaysAvailable'] === true
        );

        $query->filter = new LogicalAnd(
            array(
                new DocumentTypeId($documentTypeIdentifier),
                $query->filter,
                $this->getCoreCriterion($languages, $useAlwaysAvailable),
            )
        );
    }

    /**
     * Returns a filtering condition for the given language settings.
     *
     * The condition ensures the same Content will be matched only once across all
     * targeted translation endpoints.
     *
     * @param string[] $languageCodes
     * @param bool $useAlwaysAvailable
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Query\Criterion
     */
    private function getCoreCriterion(array $languageCodes, $useAlwaysAvailable)
    {
        // Handle languages if given
        if (!empty($languageCodes)) {
            // Get condition for prioritized languages fallback
            $filter = $this->getLanguageFilter($languageCodes);

            // Handle always available fallback if used
            if ($useAlwaysAvailable) {
                // Combine conditions with OR
                $filter = new LogicalOr(
                    array(
                        $filter,
                        $this->getAlwaysAvailableFilter($languageCodes),
                    )
                );
            }

            // Return languages condition
            return $filter;
        }

        // Otherwise search only main languages

        // 1. Main translations in main translation core if configured
        if ($this->endpointResolver->hasMainLanguagesEndpoint()) {
            return new IndexedMainTranslationCore(IndexedMainTranslationCore::MAIN_CORE);
        }

        // 2. Else just limited to main translations
        return new IndexedMainTranslation(IndexedMainTranslation::MAIN);
    }

    /**
     * Returns criteria for prioritized languages fallback.
     *
     * @param string[] $languageCodes
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Query\Criterion
     */
    private function getLanguageFilter(array $languageCodes)
    {
        $languageFilters = array();

        foreach ($languageCodes as $languageCode) {
            // Include language
            $condition = new IndexedLanguageCode($languageCode);
            // Get list of excluded languages
            $excluded = $this->getExcludedLanguageCodes($languageCodes, $languageCode);

            // Combine if list is not empty
            if (!empty($excluded)) {
                $condition = new LogicalAnd(
                    array(
                        $condition,
                        new LogicalNot(
                            new LanguageCode($excluded)
                        ),
                    )
                );
            }

            $languageFilters[] = $condition;
        }

        // Combine language fallback conditions with OR
        if (count($languageFilters) > 1) {
            $languageFilters = array(new LogicalOr($languageFilters));
        }

        // Exclude main languages index if used
        if ($this->endpointResolver->hasMainLanguagesEndpoint()) {
            $languageFilters[] = new LogicalNot(
                new IndexedMainTranslationCore(IndexedMainTranslationCore::MAIN_CORE)
            );
        }

        // Combine conditions
        if (count($languageFilters) > 1) {
            return new LogicalAnd($languageFilters);
        }

        return reset($languageFilters);
    }

    /**
     * Returns criteria for always available translation fallback.
     *
     * @param string[] $languageCodes
     *
     * @return \eZ\Publish\API\Repository\Values\Content\Query\Criterion
     */
    private function getAlwaysAvailableFilter(array $languageCodes)
    {
        $conditions = array(
            // Include always available main language translations
            new IndexedAlwaysAvailable(IndexedAlwaysAvailable::AVAILABLE),
            // Exclude all given languages
            new LogicalNot(
                new LanguageCode($languageCodes)
            ),
        );

        // Additionally include only from main translations core if used
        if ($this->endpointResolver->hasMainLanguagesEndpoint()) {
            $conditions[] = new IndexedMainTranslationCore(IndexedMainTranslationCore::MAIN_CORE);
        }

        // Combine conditions
        return new LogicalAnd($conditions);
    }

    /**
     * Returns a list of language codes to be excluded when matching translation in given
     * $selectedLanguageCode.
     *
     * If $selectedLanguageCode is omitted, all languages will be returned.
     *
     * @param string[] $languageCodes
     * @param null|string $selectedLanguageCode
     *
     * @return string[]
     */
    private function getExcludedLanguageCodes(array $languageCodes, $selectedLanguageCode = null)
    {
        $excludedLanguageCodes = array();

        foreach ($languageCodes as $languageCode) {
            if ($selectedLanguageCode !== null && $languageCode === $selectedLanguageCode) {
                break;
            }

            $excludedLanguageCodes[] = $languageCode;
        }

        return $excludedLanguageCodes;
    }
}
