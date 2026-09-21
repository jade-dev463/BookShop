<?php

namespace App\Entity;

use App\Repository\PictureRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Serializer\Attribute\Groups;
use Vich\UploaderBundle\Validator\Constraints as VichAssert;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: PictureRepository::class)]
#[Vich\Uploadable]
class Picture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Vich\UploadableField(mapping: 'book_front_cover', fileNameProperty: 'frontCover')]
    #[VichAssert\FileRequired(target: 'image')]
    private ?File $frontCoverFile = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getListing'])]
    private ?string $frontCover = null;

    #[Vich\UploadableField(mapping: 'book_back_cover', fileNameProperty: 'backCover')]
    #[VichAssert\FileRequired(target: 'image')]
    private ?File $backCoverFile = null;

    #[ORM\Column(length: 255)]
    #[Groups(['getListing'])]
    private ?string $backCover = null;

    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToOne(inversedBy: 'picture', cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Listing $listing = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setFrontCoverFile(?File $frontCoverFile = null): void
    {
        $this->frontCoverFile = $frontCoverFile;

        if (null !== $frontCoverFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getFrontCoverFile(): ?File
    {
        return $this->frontCoverFile;
    }

    public function getFrontCover(): ?string
    {
        if (!$this->frontCover) {
            return null;
        }

        return '/uploads/front_cover/' . $this->frontCover;
    }

    public function setFrontCover(string $frontCover): static
    {
        $this->frontCover = $frontCover;

        return $this;
    }

    public function setBackCoverFile(?File $backCoverFile = null): void
    {
        $this->backCoverFile = $backCoverFile;

        if (null !== $backCoverFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getBackCoverFile(): ?File
    {
        return $this->backCoverFile;
    }


    public function getBackCover(): ?string
    {
        if (!$this->backCover) {
            return null;
        }

        return '/uploads/back_cover/' . $this->backCover;
    }

    public function setBackCover(string $backCover): static
    {
        $this->backCover = $backCover;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getListing(): ?Listing
    {
        return $this->listing;
    }

    public function setListing(Listing $listing): static
    {
        $this->listing = $listing;

        return $this;
    }
}
