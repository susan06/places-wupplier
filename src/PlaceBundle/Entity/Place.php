<?php

namespace PlaceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Place
 *
 * @ORM\Table(name="place")
 * @ORM\Entity(repositoryClass="PlaceBundle\Repository\PlaceRepository")
 */
class Place
{

    /**
     * Many Features have One Product.
     * @ORM\ManyToOne(targetEntity="Location", inversedBy="places")
     */
    private $location;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="location_id", type="integer")
     */
    private $locationId;

    /**
     * @var int
     *
     * @ORM\Column(name="venues_id", type="string", length=255)
     */
    private $venuesId;

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set locationId
     *
     * @param integer $locationId
     *
     * @return Place
     */
    public function setLocationId($locationId)
    {
        $this->locationId = $locationId;

        return $this;
    }

    /**
     * Get locationId
     *
     * @return int
     */
    public function getLocationId()
    {
        return $this->locationId;
    }

    /**
     * Set venuesId
     *
     * @param integer $venuesId
     *
     * @return Place
     */
    public function setVenuesId($venuesId)
    {
        $this->venuesId = $venuesId;

        return $this;
    }

    /**
     * Get venuesId
     *
     * @return int
     */
    public function getVenuesId()
    {
        return $this->venuesId;
    }


    /**
     * Set location
     *
     * @param \PlaceBundle\Entity\Location $location
     *
     * @return Place
     */
    public function setLocation(\PlaceBundle\Entity\Location $location = null)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get location
     *
     * @return \PlaceBundle\Entity\Location
     */
    public function getLocation()
    {
        return $this->location;
    }
}
