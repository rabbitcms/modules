<?php
declare(strict_types=1);
namespace RabbitCMS\Modules\Contracts;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Interface PackageContract
 * @package RabbitCMS\Modules\Contracts
 */
interface PackageContract extends Arrayable
{
    /**
     * Get module name.
     *
     * @return string
     */
    public function getName():string;

    /**
     * @return bool
     */
    public function isEnabled():bool;

    /**
     * Set enabled module.
     *
     * @param bool $value
     */
    public function setEnabled(bool $value = true);

    /**
     * @return bool
     */
    public function isSystem():bool;

    /**
     * Get package path.
     *
     * @param string $path
     *
     * @return string
     */
    public function getPath(string $path = ''):string;

    /**
     * Get module description.
     *
     * @return string
     */
    public function getDescription():string;
}
