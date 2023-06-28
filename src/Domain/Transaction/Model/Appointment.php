<?php

namespace Src\Domain\Transaction\Model;

class Appointment
{
    /**
     * @var null|string
     */
    private string $agentName;
    /**
     * @var null|string
     */
    private string $language;
    /**
     * @var null|string
     */
    private string $appointmentDate;

    /**
     * @var null|string
     */
    private string $appointmentTime;

    /**
     * @return string
     */
    public function getAgentName(): string
    {
        return $this->agentName;
    }

    /**
     * @param string $agentName
     */
    public function setAgentName(string $agentName): void
    {
        $this->agentName = $agentName;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->language;
    }

    /**
     * @param string $language
     */

    public function setLanguage(string $language): void
    {
        $this->language = $language;
    }

    /**
     * @return string
     */
    public function getAppointmentDate(): string
    {
        return $this->appointmentDate;
    }

    /**
     * @param string $appointmentDate
     */
    public function setAppointmentDate(string $appointmentDate): void
    {
        $this->appointmentDate = $appointmentDate;
    }

    /**
     * @return string
     */
    public function getAppointmentTime(): string
    {
        return $this->appointmentTime;
    }

    /**
     * @param string $appointmentTime
     */
    public function setAppointmentTime(string $appointmentTime): void
    {
        $this->appointmentTime = $appointmentTime;
    }
}
