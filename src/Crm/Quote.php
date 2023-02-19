<?php

namespace STS\HubSpot\Crm;

use STS\HubSpot\Api\Association;
use STS\HubSpot\Api\Collection;
use STS\HubSpot\Api\Model;

/**
 * @method Association companies()
 * @method Association contacts()
 * @method Association deals()
 * @method Association line_items()
 * @property-read Company|null $company
 * @property-read Collection $companies
 * @property-read Contact|null $contact
 * @property-read Collection $contacts
 * @property-read Deal|null $deal
 * @property-read Collection $deals
 * @property-read LineItems|null $line_item
 * @property-read Collection $line_items
 */
class Quote extends Model
{
    protected string $type = "quotes";
}