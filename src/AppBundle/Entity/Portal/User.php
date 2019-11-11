<?php

namespace AppBundle\Entity\Portal;

use JMS\Serializer\Annotation as Serializer;
use JMS\Serializer\Annotation\Groups;
use AppBundle\Model\Persistence\Entity\HumanTrait;
use AppBundle\Model\User\UserRole;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Encoder\EncoderAwareInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class User
 * @package AppBundle\Entity
 * @ORM\Entity(repositoryClass="AppBundle\Repository\UserRepository")
 * @ORM\Table(name="core_user")
 * @UniqueEntity(fields="email", message="Sorry, this email address is already in use.", groups={"registration", "edit_user"})
 * @UniqueEntity(fields="username", message="Sorry, this username is already taken.", groups={"registration", "edit_user"})
 */
class User implements AdvancedUserInterface, EncoderAwareInterface
{
    use HumanTrait;

    /**
     * @var string
     * @ORM\Column(name="phone", type="string", length=20, nullable=true)
     * @Assert\NotBlank(groups={"add_user", "edit_user", "edit_profile"})
     * @Assert\Regex(
     *     pattern="/(\+(9[976]\d|8[987530]\d|6[987]\d|5[90]\d|42\d|3[875]\d|2[98654321]\d|9[8543210]|8[6421]|6[6543210]|5[87654321]|4[987654310]|3[9643210]|2[70]|7|1)\d{1,14}$)/",
     *     groups={"add_user", "edit_user"}
     * )
     * @Groups({"user", "user_list"})
     */
    protected $phone;
    /**
     * @var int
     * @ORM\Column(name="disk_quota", type="integer", length=16, nullable=true)
     * @Assert\NotBlank(groups={"add_user", "edit_user"})
     * @Assert\Regex(
     *     pattern="/(^[0-9][0-9]*$)/",
     *     message="The value {{ value }} is not a valid number.",
     *     groups={"add_user", "edit_user"}
     * )
     * @Groups({"user", "user_list", "sync_ftp"})
     */
    protected $diskQuota;
    /**
     * @var bool
     * @Assert\NotNull(groups={"add_user", "edit_user"}),
     * @Groups({"user", "user_list"})
     * @ORM\Column(name="is_admin", type="boolean", options={"default" = 0})
     */
    private $admin;
    /**
     * @var int $id
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Groups({"user", "user_list", "reservation", "reservation_list", "select_list", "user_by_hash"})
     */
    private $id;
    /**
     * @var string $email
     * @ORM\Column(name="email", type="string", length=40, nullable=false, unique=true)
     * @Assert\NotBlank(groups={"add_user", "signup"})
     * @Assert\Email(groups={"add_user", "edit_user", "edit_profile", "signup"})
     * @Groups({"user", "user_list"})
     */
    private $email;
    /**
     * @var string $username
     * @ORM\Column(name="username", type="string", length=40, nullable=false, unique=true)
     * @Assert\NotBlank(groups={"add_user", "edit_user"})
     * @Groups({"user", "user_list", "sync_ftp"})
     */
    private $username;
    /**
     * @var string $password
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     */
    private $password = '';
    /**
     * @var string $plainPassword
     * @Assert\NotBlank(groups={"add_user", "signup"})
     * @Assert\Regex(
     *     pattern="/(\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[\W])\S*)/",
     *     message="Password of at least length 8 and it containing at least one lowercase letter, at least one uppercase letter, at least one number and at least a special character (non-word characters).",
     *     groups={"add_user", "signup"}
     * )
     */
    private $plainPassword = '';
    /**
     * @var string
     * @ORM\Column(name="password_recovery_hash", type="string", length=255, nullable=true)
     */
    private $passwordRecoveryHash = '';
    /**
     * @var string $salt
     * @ORM\Column(name="salt", type="string", length=255, nullable=false)
     */
    private $salt = '';
    /**
     * @var bool
     * @Groups({"user"})
     * @ORM\Column(name="is_verified", type="boolean", options={"default" = 0})
     */
    private $verified = '';
    /**
     * @var bool $enabled
     * @ORM\Column(name="is_enabled", type="boolean", nullable=false)
     * @Assert\NotNull(groups={"add_user", "edit_user"}),
     * @Groups({"user", "user_list"})
     */
    private $enabled;
    /**
     * @var array $roles
     * @ORM\Column(name="roles", type="json_array", length=500, nullable=false)
     * @Groups({"user_list"})
     * @Assert\NotBlank(groups={"add_user", "edit_user", "signup"}),
     * @Assert\All({
     *     @Assert\NotBlank(groups={"add_user", "edit_user", "signup"}),
     *     @Assert\NotNull(groups={"add_user", "edit_user", "signup"}),
     *     @Assert\Choice(callback={"AppBundle\Model\User\UserRole", "getRoles"}, groups={"add_user", "edit_user", "signup"})
     * })
     */
    private $roles = [];
    /**
     * @var string
     * @ORM\Column(name="organization", type="string", length=100, nullable=true)
     * @Assert\NotBlank(groups={"add_user", "edit_user", "edit_profile"})
     * @Groups({"user", "user_list"})
     */
    private $organization;
    /**
     * @var string
     * @ORM\Column(name="api_key", type="string", length=80, nullable=true)
     * @Assert\NotBlank(groups={"add_user", "edit_user"})
     * @Groups({"user", "user_list", "sync_ftp"})
     */
    private $apiKey;

    /**
     * @ORM\ManyToOne(targetEntity="Country", cascade={"persist"})
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     * @Serializer\Groups({"user", "user_list", "add_user", "edit_user", "edit_profile"})
     */
    private $country;

    /**
     * @ORM\ManyToOne(targetEntity="State", cascade={"persist"})
     * @ORM\JoinColumn(name="state_id", referencedColumnName="id")
     * @Serializer\Groups({"user", "user_list", "add_user", "edit_user", "edit_profile"})
     */
    private $state;

    /**
     * @ORM\ManyToOne(targetEntity="City", cascade={"persist"})
     * @ORM\JoinColumn(name="city_id", referencedColumnName="id")
     * @Serializer\Groups({"user", "user_list", "add_user", "edit_user", "edit_profile"})
     */
    private $city;

    /**
     * @var string
     * @ORM\Column(name="address", type="string", length=255, nullable=true)
     * @Groups({"user", "user_list", "add_user", "edit_user", "edit_profile"})
     */
    private $address;

    /**
     * @var string
     * @ORM\Column(name="zip", type="string", length=10, nullable=true)
     * @Groups({"user", "user_list", "add_user", "edit_user", "edit_profile"})
     */
    private $zip;

    /**
     * @return string
     */
    function __toString()
    {
        return $this->getFullName();
    }

    /**
     * Checks whether the user's account has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw an AccountExpiredException and prevent login.
     *
     * @return bool true if the user's account is non expired, false otherwise
     *
     * @see AccountExpiredException
     */
    public function isAccountNonExpired()
    {
        return true;
    }

    /**
     * Checks whether the user is locked.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a LockedException and prevent login.
     *
     * @return bool true if the user is not locked, false otherwise
     *
     * @see LockedException
     */
    public function isAccountNonLocked()
    {
        return true;
    }

    /**
     * Checks whether the user's credentials (password) has expired.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a CredentialsExpiredException and prevent login.
     *
     * @return bool true if the user's credentials are non expired, false otherwise
     *
     * @see CredentialsExpiredException
     */
    public function isCredentialsNonExpired()
    {
        return true;
    }

    /**
     * Checks whether the user is enabled.
     *
     * Internally, if this method returns false, the authentication system
     * will throw a DisabledException and prevent login.
     *
     * @return bool true if the user is enabled, false otherwise
     *
     * @see DisabledException
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * Get enabled
     *
     * @return boolean
     */
    public function getEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param boolean $enabled
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }

    /**
     * @return int
     */
    public function isAdmin()
    {
        return $this->admin;
    }

    /**
     * @param boolean $admin
     */
    public function setAdmin($admin)
    {
        $this->admin = $admin;
    }

    /**
     * Returns the password used to authenticate the user.
     *
     * This should be the encoded password. On authentication, a plain-text
     * password will be salted, encoded, and then compared to this value.
     *
     * @return string The password
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password = $password;
    }

    /**
     * Returns the username used to authenticate the user.
     *
     * @return string The username
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * Removes sensitive data from the user.
     * This is important if, at any given point, sensitive information like
     * the plain-text password is stored on this object.
     */
    public function eraseCredentials()
    {
        $this->plainPassword = "ERASED";
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getPlainPassword()
    {
        return $this->plainPassword;
    }

    /**
     * @param string $plainPassword
     */
    public function setPlainPassword($plainPassword)
    {
        if (!$plainPassword) {
            return;
        }
        // Encode here.
        $passwordEncoded = $this->encodePassword($plainPassword, $this->getSalt());
        $this->plainPassword = $plainPassword;
        $this->password = $passwordEncoded;
    }

    /**
     * @param string $raw
     * @param string $salt
     * @return string
     */
    public function encodePassword($raw, $salt)
    {
        $messageDigest = new MessageDigestPasswordEncoder();
        return $messageDigest->encodePassword($raw, $salt);
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * This can return null if the password was not encoded using a salt.
     *
     * @return string|null The salt
     */
    public function getSalt()
    {
        if (!$this->salt) {
            $chars = '$!@#$%^&*()~`|;:<>,./?_abcdefghijklmnopqrstuvwxyz_ABCDEFGHIJKLMNOPQRSTUVWXYZ_0123456789';
            $randomString = substr(str_shuffle($chars), 0, 100);
            $this->salt = $randomString;
        }

        return $this->salt;
    }

    /**
     * @param string $salt
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;
    }

    /**
     * @param string $hash
     */
    public function setPasswordRecoveryHash($hash)
    {
        if (!empty($hash)) {
            $this->passwordRecoveryHash = hash('sha256', $hash . time());
        } else {
            $this->passwordRecoveryHash = null;
        }
    }

    /**
     * @return bool|string
     */
    public function getPasswordRecoveryHash()
    {
        return $this->passwordRecoveryHash;
    }

    /**
     * @return bool
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * @param bool $verified
     */
    public function setVerified(bool $verified)
    {
        $this->verified = $verified;
    }

    /**
     * @param string $role
     */
    public function addRole($role)
    {
        $roles = $this->getRoles();
        if (!$this->hasRole($role)) {
            $roles[] = $role;
        }

        $this->setRoles($roles);
    }

    /**
     * Returns the roles granted to the user.
     *
     * <code>
     * public function getRoles()
     * {
     *     return array('ROLE_USER');
     * }
     * </code>
     *
     * Alternatively, the roles might be stored on a ``roles`` property,
     * and populated in any number of different ways when the user object
     * is created.
     *
     * @return Role[] The user roles
     */
    public function getRoles()
    {
        return $this->roles;
    }

    /**
     * @param array $roles
     */
    public function setRoles($roles)
    {
        $this->roles = $roles;
    }

    /**
     * @param string $role
     * @return bool
     */
    public function hasRole($role)
    {
        return in_array($role, $this->getRoles());
    }

    /**
     * @return null|Role
     */
    public function getHighestRole()
    {
        $roles = $this->getRoles();
        $highestValue = 0;
        $highestRole = null;
        foreach ($roles as $role) {
            if (UserRole::getRolesHierarchy()[$role] > $highestValue) {
                $highestValue = UserRole::getRolesHierarchy()[$role];
                $highestRole = $role;
            }
        }
        return $highestRole;
    }

    /**
     * Gets the name of the encoder used to encode the password.
     * If the method returns null, the standard way to retrieve the encoder
     * will be used instead.
     * @return string
     */
    public function getEncoderName()
    {
        return 'CoreBundle\\Entity\\User';
    }

    /**
     * @return string
     */
    public function getOrganization()
    {
        return $this->organization;
    }

    /**
     * @param string $organization
     */
    public function setOrganization($organization)
    {
        $this->organization = $organization;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

    /**
     * @return int
     */
    public function getDiskQuota()
    {
        return $this->diskQuota;
    }

    /**
     * @param int $diskQuota
     */
    public function setDiskQuota($diskQuota)
    {
        $this->diskQuota = $diskQuota;
    }

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @return mixed
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param mixed $country
     */
    public function setCountry($country): void
    {
        $this->country = $country;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state): void
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param mixed $city
     */
    public function setCity($city): void
    {
        $this->city = $city;
    }

    /**
     * @return string
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * @param string $address
     */
    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    /**
     * @return string
     */
    public function getZip(): string
    {
        return $this->zip;
    }

    /**
     * @param string $zip
     */
    public function setZip(string $zip): void
    {
        $this->zip = $zip;
    }

    /**
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("roles_select")
     * @Groups({"user"})
     */
    public function getRolesForAngular()
    {
        $roles = $this->roles;
        $rolesArray = [];

        //get static roles data
        $modelRoles = UserRole::getRoles();
        $modelRolesAssoc = UserRole::getRolesHierarchy();

        if ($roles) {

            foreach ($roles as $role) {

                if (in_array($role, $modelRoles)) {

                    $id = $modelRolesAssoc[$role];
                    $rolesArray[] = ['id' => $id, 'itemName' => $role];
                }
            }
        }

        return $rolesArray;
    }

    /**
     * @Serializer\Groups({"select_list", "reservation", "reservation_list"})
     * @Serializer\VirtualProperty()
     * @Serializer\SerializedName("itemName")
     */
    public function getUserNameForApi()
    {
        return $this->getFullName();
    }
}
