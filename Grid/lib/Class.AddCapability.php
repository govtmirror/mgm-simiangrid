<?php
/** Simian grid services
 *
 * PHP version 5
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 * 3. The name of the author may not be used to endorse or promote products
 *    derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR ``AS IS'' AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF
 * THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    SimianGrid
 * @author     Jim Radford <http://www.jimradford.com/>
 * @copyright  Open Metaverse Foundation
 * @license    http://www.debian.org/misc/bsd.license  BSD License (3 Clause)
 * @link       http://openmetaverse.googlecode.com/
 */

class AddCapability implements IGridService
{
    private $CapabilityID;
    private $OwnerID;

    public function Execute($db, $params)
    {
        if (!isset($params["OwnerID"], $params["Resource"], $params["Expiration"]) ||
            !UUID::TryParse($params["OwnerID"], $this->OwnerID))
        {
            header("Content-Type: application/json", true);
            echo '{ "Message": "Invalid parameters" }';
            exit();
        }
        
        if (!isset($params["CapabilityID"]) || !UUID::TryParse($params["CapabilityID"], $this->CapabilityID))
            $this->CapabilityID = UUID::Random();
        
        $resource = $params["Resource"];
        $expiration = intval($params["Expiration"]);
        
        // Sanity check the expiration date
        if ($expiration <= time())
        {
            header("Content-Type: application/json", true);
            echo '{ "Message": "Invalid expiration date ' . $expiration . '" }';
            exit();
        }
        
        log_message('debug', "Creating capability " . $this->CapabilityID . " owned by " . $this->OwnerID . " mapping to $resource until $expiration");
        
        $sql = "INSERT INTO Capabilities (ID, OwnerID, Resource, ExpirationDate) VALUES (:ID, :OwnerID, :Resource, FROM_UNIXTIME(:ExpirationDate))
                ON DUPLICATE KEY UPDATE ID=VALUES(ID), Resource=VALUES(Resource), ExpirationDate=VALUES(ExpirationDate)";
        
        $sth = $db->prepare($sql);
        
        if ($sth->execute(array(':ID' => $this->CapabilityID , ':OwnerID' => $this->OwnerID , ':Resource' => $resource , ':ExpirationDate' => $expiration)))
        {
            header("Content-Type: application/json", true);
            echo sprintf('{"Success": true, "CapabilityID": "%s"}', $this->CapabilityID);
            exit();
        }
        else
        {
            log_message('error', sprintf("Error occurred during query: %d %s", $sth->errorCode(), print_r($sth->errorInfo(), true)));
            log_message('debug', sprintf("Query: %s", $sql));
            
            header("Content-Type: application/json", true);
            echo '{ "Message": "Database query error" }';
            exit();
        }
    }
}
