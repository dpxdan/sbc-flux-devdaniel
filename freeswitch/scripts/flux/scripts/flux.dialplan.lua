-------------------------------------------------------------------------------------
-- Flux Telecom - Unindo pessoas e negócios
--
-- Copyright (C) 2022 Flux Telecom
-- Daniel Paixao <daniel@flux.net.br>
-- Flux SBC Version 4.0 and above
-- License https://www.gnu.org/licenses/agpl-3.0.html
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU Affero General Public License as
-- published by the Free Software Foundation, either version 3 of the
-- License, or (at your option) any later version.
-- 
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU Affero General Public License for more details.
-- 
-- You should have received a copy of the GNU Affero General Public License
-- along with this program.  If not, see <http://www.gnu.org/licenses/>.
--------------------------------------------------------------------------------------

destination_number = params:getHeader("Caller-Destination-Number")
callerid_number = params:getHeader('Caller-Caller-ID-Number')

if (destination_number == nil) then
    return;
end

Logger.info("[Dialplan] Dialed number : "..destination_number)
Logger.info("[Dialplan] Caller number : "..callerid_number)

if (params:getHeader("variable_sip_h_P-Voice_broadcast") == 'true') then
	destination_number = params:getHeader("variable_sip_h_P-cb_destination")
	Logger.info("Clicktocall Destination Number " ..destination_number)
end

local cc_access_number = get_cc_access_number(destination_number)
if (cc_access_number and cc_access_number['access_number'] ~= '' and cc_access_number['access_number'] == destination_number) then
        if (destination_number == cc_access_number['access_number']) then
            callerid_number = params:getHeader('variable_effective_caller_id_number');
            generate_cc_facilities(destination_number,callerid_number);
            return;
        end
end
----------------------- END CALLING CARD SECTION -------------------------------

--@TODO: We will take it to Feature code module
------------------------- VOICEMAIL LISTEN START--------------------------------------
if(tonumber(config['voicemail_number']) == tonumber(destination_number)) then
Logger.info("[Dialplan] VOICEMAIL : ")
	xml = xml_voicemail(xml,destination_number)
return;
end
------------------------- VOICEMAIL LISTEN END --------------------------------------

---- Getting callerid for localization feature ---- 
if (params:getHeader('variable_effective_caller_id_number') ~= nil) then
    callerid_number = params:getHeader('variable_effective_caller_id_number') or ""
    callerid_name = params:getHeader('variable_effective_caller_id_name') or ""
else
    callerid_number = params:getHeader('Caller-Caller-ID-Number') or ""
    callerid_name = params:getHeader('Caller-Caller-ID-Name') or ""
end       


--To override custom callerid from addon
if custom_callerid then custom_callerid() end     
Logger.info("[Dialplan] Caller Id name / number  : "..callerid_name.." / "..callerid_number)

--Saving caller id information in array
callerid_array = {}
callerid_array['cid_name'] = callerid_name
callerid_array['cid_number'] = callerid_number
callerid_array['original_cid_name'] = callerid_name
callerid_array['original_cid_number'] = callerid_number
--------------------------------------

-- Define default variables 
call_direction = 'outbound'
local calltype = 'Padrao'
local custom_calltype = 'Padrao'
local call_type = 'Padrao'
local accountcode = ''
local sipcall = ''
local auth_type = 'default'
local authinfo = {}
local accountname = 'default'
local original_destination_number=''
package_id = 0
if (params:getHeader('variable_accountcode') ~= nil) then
	accountcode = params:getHeader("variable_accountcode")
	Logger.info("[Dialplan] Accountcode TESTE : ".. accountcode)
	accountname = accountcode
end


--accountcode = params:getHeader("variable_accountcode")
account_user = params:getHeader("variable_sip_h_P-Accountcode")
if(account_user ~= '' and account_user ~= nil) then
Logger.info("[Dialplan] Accountcode DEBUG : ".. account_user)
accountname = account_user
end

--To override custom calltype
if custom_calltype then
	calltype_custom = custom_calltype
	if(calltype_custom ~= '' and calltype_custom ~= nil) then
		calltype = calltype_custom 
	end
end

--To override custom accountcode
if accountcode then
	accountcode_custom = accountcode
	if(accountcode_custom ~= '' and accountcode_custom ~= nil) then
		accountcode = accountcode_custom
		accountname = accountcode_custom 
	end
end
sipcall = params:getHeader("variable_sipcall")
call_direction = define_call_direction(destination_number,accountcode,config,callerid_number)

Logger.info("[Dialplan] Call direction DEBUG : ".. call_direction)

--if (didinfo ~= nil) then
--	accountcode=didinfo['account_code']
--	did_provider=didinfo['provider_id']
--	Logger.info("[Dialplan] accountcode : ".. accountcode)
--	Logger.info("[Dialplan] provider : ".. did_provider)
--end



-- If no account code found then do further authentication of call

if (accountcode == nil or accountcode == '') then

    from_ip = ""	
    if(config['opensips']=='8') then
--        from_ip = "200.159.177.13"
--    	from_ip = params:getHeader("variable_sip_h_X-AUTH-IP")
    else
    	from_ip = params:getHeader('Hunt-Network-Addr')
    	Logger.info("[Dialplan] Call direction DEBUG : IP AUTH")
    	--Logger.info("[Dialplan] Accountcode DEBUG : ".. didinfo['accountid'])
    end	

    authinfo = doauthentication(destination_number,from_ip)

    if (authinfo ~= nil and authinfo['type'] == 'acl') then      
    	accountcode = authinfo['account_code']
        if (authinfo['prefix'] ~= '') then
            destination_number = do_number_translation(authinfo['prefix'].."/*",destination_number)
            --accountcode = didinfo['accountid']
        end
    	auth_type = 'acl';
    	accountname = authinfo['name'] or ""
    	--accountcode = didinfo['accountid']
    end
end

if (accountcode == nil or accountcode == "") then
	accountcode = params:getHeader("variable_sip_h_P-Accountcode")
end

-- Still no account code that means call is not authenticated.
if (accountcode == nil or accountcode == "") then
  Logger.info("[Dialplan] Call authentication fail..!!"..config['playback_audio_notification'])
  error_xml_without_cdr(destination_number,"AUTHENTICATION_FAIL",calltype,config['playback_audio_notification'],'0') 
  return
end

Logger.info("[Accountcode : ".. accountcode .."]" );


--Destination number string 
number_loop_str = number_loop(destination_number,'blocked_patterns')
number_loop_str_dest = number_loop(destination_number,'pattern')
--number_loop_str_orig = number_loop(callerid_number,'pattern')


-- Do authorization
userinfo = doauthorization("number",accountcode,call_direction,destination_number,number_loop_str,config)

--------------------------------------- SPEED DIAL --------------------------------------
if(string.len(destination_number) == 1 ) then
	destination_number = get_speeddial_number(destination_number,userinfo['id'])
	infouser = userinfo['id']
	Logger.info("[Dialplan] INFO USER : "..infouser)
	Logger.info("[Dialplan] SPEED DIAL NUMBER : "..destination_number)
    
    -- Overriding call direction if speed dial destination is for DID or local extension 
    call_direction = define_call_direction(destination_number,accountcode,config)
    Logger.info("[Dialplan] New Call direction : ".. call_direction)
end
-----------------------------------------------------------------------------------------


is_did_check = is_did(destination_number,config,callerid_number);
if (is_did_check ~= nil and is_did_check['id']) then
    Logger.info("[Dialplan] New Call direction HEREE : ".. call_direction)
	error_xml_without_cdr(destination_number,"NO_ROUTE_DESTINATION",calltype,config['playback_audio_notification'],userinfo['id'])
	return 0
end	

if(userinfo ~= nil) then
	if (didinfo ~= nil and didinfo['status']=='1') then 
		error_xml_without_cdr(destination_number,"UNALLOCATED_NUMBER",calltype,config['playback_audio_notification'],userinfo['id'])
		return 0
	end

	if(userinfo['ACCOUNT_ERROR'] == 'DESTINATION_BLOCKED') then
		error_xml_without_cdr(destination_number,"DESTINATION_BLOCKED",calltype,config['playback_audio_notification'],userinfo['id'])
		return 0
	end

	package_array = package_calculation (destination_number,userinfo,call_direction)

	if(userinfo['ACCOUNT_ERROR'] == 'NO_SUFFICIENT_FUND') then
		error_xml_without_cdr(destination_number,"NO_SUFFICIENT_FUND",calltype,config['playback_audio_notification'],userinfo['id'])
		return 0
	end
    if(userinfo['ACCOUNT_ERROR'] == 'ACCOUNT_EXPIRE') then
		error_xml_without_cdr(destination_number,"ACCOUNT_EXPIRE",calltype,config['playback_audio_notification'],userinfo['id'])
		return 0
    end    
    if(userinfo['ACCOUNT_ERROR'] == 'ACCOUNT_INACTIVE_DELETED') then
		local accountid = 0
		if(userinfo['id'] and tonumber(userinfo['id']) > 0)then accountid = userinfo['id'] end
	Logger.info("accountid : ".. accountid );
		error_xml_without_cdr(destination_number,"ACCOUNT_INACTIVE_DELETED",calltype,config['playback_audio_notification'],accountid)
		return 0
    end

	-- Code for Prefix based routing to select rate group
	original_destination_number = destination_number	
		
	userinfo = package_array[1]
	package_maxlength = package_array[2] or ""	
    -------------------------------------------------

	if(userinfo['ACCOUNT_ERROR'] == 'NO_SUFFICIENT_FUND') then
		error_xml_without_cdr(destination_number,"NO_SUFFICIENT_FUND",calltype,config['playback_audio_notification'],userinfo['id'])
		return 0
	end

	if(userinfo['local_call'] == '1' and call_direction == "local") then
        Logger.info("[Functions] [DOAUTHORIZATION] ["..accountcode.."] LOCAL CALL IS DISABLE....!!");
		call_direction = 'outbound'
	end

end
--Check Ported number

if(call_direction == 'outbound')then
	if(addon_list  and addon_list['portednumber'] ~= '')then
		if get_ported_number then destination_number = get_ported_number(destination_number); end
	end
end

if (userinfo ~= nil) then  
    
	if (config['realtime_billing'] == "0") then
		local nibble_id = ""
		local nibble_rate = ""
		local nibble_connect_cost = ""
		local nibble_init_inc = ""
		local nibble_inc = ""
	end
    
	-- print customer information 
	Logger.info("=============== Account Information ===================")
	Logger.info("User id : "..userinfo['id'])  
	Logger.info("Account code : "..userinfo['number'])
	Logger.info("Company : "..userinfo['company_name'])
	Logger.info("Balance : "..get_balance(userinfo,'',config))  
	Logger.info("Type : "..userinfo['posttoexternal'].." [0:prepaid,1:postpaid]")  
	Logger.info("Ratecard id : "..userinfo['pricelist_id'])  
	Logger.info("========================================================")    
    
    if (tonumber(userinfo['localization_id']) > 0) then
    	or_localization = get_localization(userinfo['localization_id'],'O')
    end

	-- If call is pstn and dialed modify defined then do number translation
	if (call_direction == 'outbound' and tonumber(userinfo['localization_id']) > 0 and or_localization and or_localization['number_originate'] ~= nil) then	
		or_localization['number_originate'] = or_localization['number_originate']:gsub(" ", "")			
		destination_number = do_number_translation(or_localization['number_originate'],destination_number)
	end


	if (or_localization and tonumber(userinfo['localization_id']) > 0 and or_localization['out_caller_id_originate'] ~= nil and call_direction ~= 'inbound') then        
		or_localization['out_caller_id_originate'] = or_localization['out_caller_id_originate']:gsub(" ", "")	
		callerid_array['original_cid_name'] = do_number_translation(or_localization['out_caller_id_originate'],callerid_array['cid_name'])
		callerid_array['original_cid_number'] = do_number_translation(or_localization['out_caller_id_originate'],callerid_array['cid_number'])  		 
		callerid_array['cid_name'] = do_number_translation(or_localization['out_caller_id_originate'],callerid_array['cid_name'])
		callerid_array['cid_number'] = do_number_translation(or_localization['out_caller_id_originate'],callerid_array['cid_number'])        
	end
    --END

	if(call_direction == 'inbound' and config['did_global_translation'] ~= nil and config['did_global_translation'] ~= '' and tonumber(config['did_global_translation']) > 0) then
		-- @TODO: Implement localization for DID global translation
		--destination_number = do_number_translation(config['did_global_translation'],destination_number)
		destination_number = didinfo['did_number']
		number_loop_str_orig = number_loop(callerid_number,'pattern')
--		number_loop_str_orig = number_loop(callerid_number)
	end     

  	number_loop_str = number_loop(destination_number)
  	
if (didinfo ~= nil) then
	accountcode=didinfo['account_code']
	did_provider=didinfo['provider_id']
	Logger.info("[Dialplan] accountcode : ".. accountcode)
	Logger.info("[Dialplan] provider : ".. did_provider)
end

	-- Fine max length of call based on origination rates.
	origination_array = get_call_maxlength(userinfo,destination_number,call_direction,number_loop_str,config,didinfo,callerid_number)
	    
	if( origination_array == 'NO_SUFFICIENT_FUND' or origination_array == 'ORIGINATION_RATE_NOT_FOUND' or origination_array == 'NO_ROUTE_DESTINATION') then
	    error_xml_without_cdr(destination_number,origination_array,calltype,config['playback_audio_notification'],userinfo['id']) 
	    return
	end
	
	maxlength = origination_array[1]
	user_rates = origination_array[2]
	xml_user_rates = origination_array[3] or ""

	if (config['realtime_billing'] == "0" and call_direction == 'outbound') then		
		nibble_id = userinfo['id']
		nibble_rate = user_rates['cost']
		nibble_connect_cost = user_rates['connectcost']
	    nibble_init_inc = user_rates['init_inc']
	    nibble_inc = user_rates['inc']
	end	

	if (config['realtime_billing'] == "0" and call_direction == 'inbound' and didinfo['reverse_rate'] == '0') then	
		did_reverse = didinfo['reverse_rate']
		Logger.info("Customer Reverse Rate : "..did_reverse)
		Logger.info("Customer Realtime : "..config['realtime_billing'])	
--		nibble_id = userinfo['id']
--		nibble_rate = user_rates['cost']
--		nibble_connect_cost = user_rates['connectcost']
--	    nibble_init_inc = user_rates['init_inc']
--	    nibble_inc = user_rates['inc']
	end	
	
	if(package_maxlength ~= "") then	
		maxlength=package_maxlength
	end   
    
	local reseller_ids = {}
	local i = 1
    local reseller_cc_limit = ""
    
	--For live call report display
	-- livecall_data = userinfo['first_name'].."("..userinfo['number']..")|||"..user_rates['pattern'].." // "..user_rates['comment'].." // "..user_rates['cost']   
	 
	if(user_rates['trunk_id'] ~= nil) then
		livecall_data = userinfo['first_name'].."("..userinfo['number']..")|||"..user_rates['pattern'].." // "..user_rates['comment'].." // "..user_rates['cost'].." // trunk_id="..user_rates['trunk_id']   
	else
	 	livecall_data = userinfo['first_name'].."("..userinfo['number']..")|||"..user_rates['pattern'].." // "..user_rates['comment'].." // "..user_rates['cost']    
	end  
	
	------------

	-- Set customer information in new variable
	customer_userinfo = userinfo
	rate_carrier_id = user_rates['trunk_id']
--    Logger.info("Rate carrier ID: "..rate_carrier_id)
	--For live call report display
	livecall_reseller = "x"
	------------
    
	while (tonumber(userinfo['reseller_id']) > 0 and tonumber(maxlength) > 0 ) do
		number_loop_str = number_loop(destination_number,'blocked_patterns') 
		Logger.info("FINDING LIMIT FOR RESELLER: "..userinfo['reseller_id'])

		reseller_userinfo = doauthorization("id",userinfo['reseller_id'],call_direction,destination_number,number_loop_str,config)


        if(customer_userinfo['pricelist_id_admin'] ~="" and tonumber(customer_userinfo['pricelist_id_admin']) ~=0  and customer_userinfo['pricelist_id_admin'] ~= nil)then
				reseller_userinfo['pricelist_id']=customer_userinfo['pricelist_id_admin'];
			        if(tonumber(customer_userinfo['pricelist_id_admin']) ~= 0)then
					Logger.info("[Prefix Base Routing] Replace customer_userinfo pricelist id: "..reseller_userinfo['pricelist_id'])
				end
		end		
        if(reseller_userinfo['ACCOUNT_ERROR'] == 'ACCOUNT_INACTIVE_DELETED') then
		    error_xml_without_cdr(destination_number,"ACCOUNT_INACTIVE_DELETED",calltype,config['playback_audio_notification'],userinfo)
		    return 0
	    end

	    -- @TODO: Need to remove package selection for reseller as we have changed concept of package usage
        -- Get package information of reseller
	    -- package_array = package_calculation (destination_number,reseller_userinfo,call_direction)
		

	    if(reseller_userinfo['ACCOUNT_ERROR'] == 'NO_SUFFICIENT_FUND') then
		    error_xml_without_cdr(destination_number,"NO_SUFFICIENT_FUND",calltype,config['playback_audio_notification'],reseller_userinfo['id'])
		    return 0
	    end
    
    	-- @TODO: Remove number translation / localization for reseller as we will apply localization to customer directly
		number_loop_str = number_loop(destination_number)
--		number_loop_str_orig = number_loop(callerid_number,'pattern')
		number_loop_str_orig = number_loop(callerid_number)
		reseller_ids[i] = reseller_userinfo
	    
	    --For live call report display
    	livecall_reseller = livecall_reseller.."//"..reseller_userinfo['id']
		--------------
	    
		-- print reseller information 
		Logger.info("=============== Reseller Information ===================")
		Logger.info("User id : "..reseller_userinfo['id'])  
		Logger.info("Account code : "..reseller_userinfo['number'])
		Logger.info("Balance : "..get_balance(reseller_userinfo))  
		Logger.info("Type : "..reseller_userinfo['posttoexternal'].." [0:prepaid,1:postpaid]")  
		Logger.info("Ratecard id : "..reseller_userinfo['pricelist_id'])  
		
		origination_array_reseller=get_call_maxlength(reseller_userinfo,destination_number,call_direction,number_loop_str,config,didinfo,callerid_number)

        if( origination_array_reseller == 'NO_SUFFICIENT_FUND' or origination_array_reseller == 'ORIGINATION_RATE_NOT_FOUND') then
            error_xml_without_cdr(destination_number,origination_array_reseller,calltype,1,customer_userinfo['id']) 
    	    return
    	end 

		reseller_maxlength = origination_array_reseller[1];
		reseller_rates = origination_array_reseller[2];
		xml_reseller_rates = origination_array_reseller[3];

				
		if (config['realtime_billing'] == "0" and call_direction == 'outbound') then
			-------------NIBBLE BILLING PARAM SET STARTS----------------------------
			nibble_id = nibble_id..","..reseller_userinfo['id']
			nibble_rate = nibble_rate..","..reseller_rates['cost']
			nibble_connect_cost = nibble_connect_cost..","..reseller_rates['connectcost']
			nibble_init_inc = nibble_init_inc..","..reseller_rates['init_inc']
			nibble_inc = nibble_inc..","..reseller_rates['inc']
			-------------NIBBLE BILLING PARAM SET ENDS----------------------------
		end
		
	--	if (config['realtime_billing'] == "0") then
			-------------NIBBLE BILLING PARAM SET STARTS----------------------------
	--		nibble_id = nibble_id..","..reseller_userinfo['id']
	--		nibble_rate = nibble_rate..","..reseller_rates['cost']
	--		nibble_connect_cost = nibble_connect_cost..","..reseller_rates['connectcost']
	--		nibble_init_inc = nibble_init_inc..","..reseller_rates['init_inc']
	--		nibble_inc = nibble_inc..","..reseller_rates['inc']
			-------------NIBBLE BILLING PARAM SET ENDS----------------------------
	--	end
		

		xml_user_rates = xml_user_rates.."||"..xml_reseller_rates
		Logger.info("Reseller xml_user_rates : "..xml_user_rates)  
		Logger.info("========================================================")  
        -- If reseller has free seconds then override max length variable with it. 
        if(package_maxlength ~= "") then	
	        xml_reseller_rates=package_maxlength
        end  

		if (tonumber(reseller_maxlength) < tonumber(maxlength)) then 
			maxlength = reseller_maxlength
		end

        -- ITPL : Added checkout for reseller concurrent calls.    
        if (tonumber(reseller_userinfo['maxchannels']) > 0 or tonumber(reseller_userinfo['cps']) > 0) then
            reseller_cc_limit = set_cc_limit_resellers(reseller_userinfo)
        end
        	--~ if (tonumber(reseller_maxlength) < 1 or tonumber(reseller_rates['cost']) > tonumber(user_rates['cost'])) then
		rate_carrier_id = reseller_rates['trunk_id']
		userinfo = reseller_userinfo
	end -- End while 

	
	if (config['realtime_billing'] == "0" and call_direction == 'outbound') then
		Logger.info("NIBBLE ID "..nibble_id)
		Logger.info("NIBBLE RATE "..nibble_rate)
		Logger.info("NIBBLE CONNECT COST "..nibble_connect_cost)
		Logger.info("NIBBLE INITIAL INC "..nibble_init_inc)
		Logger.info("NIBBLE INC "..nibble_inc)
    
--		customer_userinfo["nibble_accounts"] = nibble_id
--    	customer_userinfo["nibble_rates"] = nibble_rate
--    	customer_userinfo["nibble_connect_cost"] = nibble_connect_cost
--    	customer_userinfo["nibble_init_inc"] = nibble_init_inc
--    	customer_userinfo["nibble_inc"] = nibble_inc
	end
	
	--- Reseller validation ends
	if ( tonumber(maxlength) <= 0 ) then
	    error_xml_without_cdr(destination_number,"NO_SUFFICIENT_FUND",calltype,config['playback_audio_notification'],customer_userinfo['id']);
	end


	Logger.info("Call Max length duration : "..maxlength.." minutes")
	--say_timelimit(minutes) 
	livecall_data = livecall_reseller.."|||"..livecall_data
	local xml = {}
    
	
	-- Generate dialplan for call
	if (call_direction == 'inbound') then
		-- ********* Check RECEIVER Balance and status of the Account *************
		local dialuserinfo
    if(didinfo['reverse_rate'] ~= nil and didinfo['reverse_rate'] == "0")then
		config['free_inbound'] = 1
		
		Logger.info("[DIALPLAN] STRIPCADUP IN")
--      Exemplos de patterns
--		num_pattern_0 = "^0([1-9][1-9])(\\d{7,20})$"
--		num_pattern_cn = "^([1-9][1-9])(\\d{7,8})$"
--		num_regex_pattern = "^(0([1-9][1-9]))(\\d{7,20})$"
--    num_regex_unknown = "^([0-9]\\d{1,2})([2-9]\\d{3,4})(\\d{4})$"
--		num_pattern_local = "^([2-9]\\d{3})(\\d{4})$"

--    Exemplos de replace

--		num_pattern_replace = "%1" - Retorna o grupo 1
--    num_pattern_replace = "%2" - Retorna o grupo 2

		a = callerid_number	        
		
		num_regex = callerid_number
		rgx_number = regex_cmd(num_regex,"unknown","0")
		rgx_cn_number = regex_cmd(num_regex,"unknown","1")
		rgx_prefix_number = regex_cmd(num_regex,"unknown","2")
		rgx_end_number = regex_cmd(num_regex,"unknown","3")
		
		
		if(rgx_cn_number ~= nil and rgx_cn_number ~= "false") then 
		rgx_number_len = string.len(rgx_cn_number)
		if(rgx_number_len > 2) then
		rgx_cn_dest_number = string.sub(rgx_cn_number, 2, 3)
		rgx_dest_number = rgx_cn_dest_number..rgx_prefix_number..rgx_end_number
		else
		rgx_cn_dest_number = rgx_cn_number
		rgx_dest_number = rgx_number
		end
		
		cn_dest_number = rgx_cn_dest_number
		area_number = rgx_cn_dest_number
		prefix_dest_number = rgx_prefix_number
		end_dest_number = rgx_end_number
		carrier_dest_number = rgx_dest_number
		Logger.info("[DIALPLAN] FUNCTION cn_dest_number: "..cn_dest_number)
		Logger.info("[DIALPLAN] FUNCTION area_number: "..area_number)
		Logger.info("[DIALPLAN] FUNCTION carrier_dest_number: "..carrier_dest_number)
		Logger.info("[DIALPLAN] FUNCTION prefix_dest_number: "..prefix_dest_number)
		Logger.info("[DIALPLAN] FUNCTION end_dest_number: "..end_dest_number)
		end
		
		
		Logger.info("=============== Carrier Information ===================")
		Logger.info("cn_dest_number : "..cn_dest_number) 
		Logger.info("area_number : "..area_number)  
		Logger.info("prefix_dest_number : "..prefix_dest_number)  
		Logger.info("end_dest_number : "..end_dest_number)
		Logger.info("carrier_dest_number : "..carrier_dest_number)
		Logger.info("destination_number : "..destination_number)
		Logger.info("callerid_number : "..callerid_number)
		didinfo['cn_dest_number'] = cn_dest_number

		Logger.info("================================================================")  	    		 
		carrier_info = get_carrier_in(didinfo,cn_dest_number,carrier_dest_number)
		if(carrier_info ~= nil and carrier_info['carrier_rn1'] ~= nil) then
		
		-- Get termination RN1
		didinfo['rn1'] = carrier_info['carrier_rn1']
		didinfo['idCadup'] = carrier_info['idCadup']
		didinfo['nomePrestadora'] = carrier_info['nomePrestadora']
		didinfo['carrier_id'] = carrier_info['carrier_id']
		didinfo['nomeLocalidade'] = carrier_info['nomeLocalidade']
		didinfo['areaLocal'] = carrier_info['areaLocal']
		didinfo['tipo'] = carrier_info['tipo']
		if(didinfo['tipo'] == 'M' ) then
		didinfo['tipo'] = 'Movel'
		else
		didinfo['tipo'] = 'Fixo'
		end	
		didinfo['prefixo'] = carrier_info['prefixo']
		didinfo['codArea'] = carrier_info['codArea']
		didinfo['uf'] = carrier_info['uf']		
		didinfo['carrier_rn1'] = carrier_info['carrier_rn1']
		didinfo['call_count'] = carrier_info['call_count']
		didinfo['carrier_name'] = carrier_info['carrier_name']
		didinfo['carrier_route_id'] = carrier_info['carrier_route_id']
		didinfo['rate_carrier_id'] = carrier_info['rn1']
		didinfo['pattern'] = carrier_info['pattern']
--		check_carrier = user_rates['check_carrier']
		else
		didinfo['idCadup'] = 0
		didinfo['carrier_rn1'] = 0
		didinfo['rn1'] = 0
		end
		else
		config['free_inbound'] = 0
		didinfo['idCadup'] = 0
		didinfo['carrier_rn1'] = 0
		didinfo['rn1'] = 0
		end		
		Logger.info("[userinfo] INB_FREE:" .. INB_FREE)
		Logger.info("[userinfo] free_inbound:" .. config['free_inbound'])

		callerid = get_override_callerid(customer_userinfo,callerid_name,callerid_number)
		if (callerid['cid_name'] ~= nil) then
			callerid_array['cid_name'] = callerid['cid_name']
			callerid_array['cid_number'] = callerid['cid_number']
			callerid_array['original_cid_name'] = callerid['cid_name']
			callerid_array['original_cid_number'] = callerid['cid_number']
		end 
		dialuserinfo = doauthorization('id',didinfo['accountid'],call_direction,destination_number,number_loop,config)	
		-- ********* Check & get Dialer Rate card information *********
		origination_array_DID = ''
--		if(tonumber(config['free_inbound']) == 1)then
		if(tonumber(config['free_inbound']) == 1 and didinfo['reverse_rate'] ~= nil and didinfo['reverse_rate'] == "0")then
		number_loop_str_orig = number_loop(callerid_number,'pattern')
		Logger.info("[userinfo] Actual free_inbound 1 origination_array_DID XML DEBUG:")
			origination_array_DID = get_call_maxlength(customer_userinfo,callerid_number,"inbound",number_loop_str_orig,config,didinfo,callerid_number)
		else
		Logger.info("[userinfo] Actual free_inbound 0 destination_array_DID XML DEBUG:")
		origination_array_DID = get_call_maxlength(customer_userinfo,destination_number,"inbound",number_loop,config,didinfo,callerid_number)		
		end
		local actual_userinfo = customer_userinfo
		Logger.info("[userinfo] Actual CustomerInfo XML DEBUG:" .. actual_userinfo['id'])
		customer_userinfo['id'] = didinfo['accountid'];
		Logger.info("[userinfo] Actual CustomerInfo XML DEBUG:" .. customer_userinfo['id'])
		
		if((origination_array_DID ~= 'ORIGINATION_RATE_NOT_FOUND' and origination_array_DID ~= 'NO_SUFFICIENT_FUND' and origination_array_DID[3] ~= nil and didinfo['reverse_rate'] ~= nil and didinfo['reverse_rate'] == "0")) then 
			Logger.info("[userinfo] Userinfo XML:" .. customer_userinfo['id']) 
			xml_did_rates = origination_array_DID[3]
			if(xml_did_rates == '' or xml_did_rates == nil)
			then 
			xml_did_rates = 0			
			end
		elseif ((origination_array_DID ~= 'ORIGINATION_RATE_NOT_FOUND' and origination_array_DID ~= 'NO_SUFFICIENT_FUND' and origination_array_DID[3] ~= nil )) then
		Logger.info("[userinfo] Userinfo XML:" .. customer_userinfo['id']) 
		xml_did_rates = origination_array_DID[3]
		if(xml_did_rates == '' or xml_did_rates == nil)
		then 
		xml_did_rates = 0			
		end		
		else
			error_xml_without_cdr(destination_number,"ORIGINATION_RATE_NOT_FOUND",calltype,config['playback_audio_notification'],customer_userinfo['id'])
			return
		end
		-- ********* END *********
		while (tonumber(customer_userinfo['reseller_id']) > 0  ) do 
			Logger.info("[WHILE DID CONDITION] FOR CHECKING RESELLER :" .. customer_userinfo['reseller_id']) 
			customer_userinfo = doauthorization('id',customer_userinfo['reseller_id'],call_direction,destination_number,number_loop,config)	
			origination_array_DID = get_call_maxlength(customer_userinfo,destination_number,"inbound",number_loop_str,config,didinfo,callerid_number)

			if(origination_array_DID ~= 'ORIGINATION_RATE_NOT_FOUND' and origination_array_DID ~= 'NO_SUFFICIENT_FUND' and origination_array_DID[3] ~= nil) then 
				Logger.info("[userinfo] Userinfo XML:" .. customer_userinfo['id']) 
				xml_did_rates = xml_did_rates .."||"..origination_array_DID[3]
			else
				error_xml_without_cdr(destination_number,"ORIGINATION_RATE_NOT_FOUND",calltype,config['playback_audio_notification'],customer_userinfo['id'])
				return
			end
		end
		-- ********* END *********
		Logger.info("[userinfo] Actual CustomerInfo XML : " .. actual_userinfo['id'])
		xml = freeswitch_xml_header(xml,destination_number,accountcode,maxlength,call_direction,accountname,xml_user_rates,actual_userinfo,config,xml_did_rates,nil,callerid_array,original_destination_number)
		if callerid_lookup then didinfo['callerid_number'] = callerid_lookup(params) end
		if(didinfo['extensions'] == '')then
			error_xml_without_cdr(destination_number,"NO_ROUTE_DESTINATION",calltype,config['playback_audio_notification'],customer_userinfo['id'])
			return
		end
		xml = freeswitch_xml_inbound(xml,didinfo,actual_userinfo,config,xml_did_rates,callerid_array,livecall_data)
		xml = freeswitch_xml_footer(xml)	   	    
		XML_STRING = table.concat(xml, "\n");
		Logger.info("[Dialplan] Generated XML:" .. XML_STRING)  
	elseif (call_direction == 'local') then
		local SipDestinationInfo;
		SipDestinationInfo = check_local_call(destination_number,callerid_number)
		
		xml = freeswitch_xml_header(xml,destination_number,accountcode,maxlength,call_direction,accountname,xml_user_rates,customer_userinfo,config,nil,nil,callerid_array,original_destination_number)

		xml = freeswitch_xml_local(xml,destination_number,SipDestinationInfo,callerid_array,livecall_data)
		xml = freeswitch_xml_footer(xml)	   	    
		XML_STRING = table.concat(xml, "\n");
		Logger.info("[Dialplan] Generated XML:\n" .. XML_STRING)  

	else		
		 force_outbound_routes =0;
   		if(rate_carrier_id ~= nil and rate_carrier_id ~= '' and rate_carrier_id ~= 0) then
			Logger.info("[DIALPLAN] Force Routes User Rate ID : ".. user_rates['id'])
			force_outbound_routes = user_rates['id']
		 end
		if(user_rates['check_carrier'] ~= nil and user_rates['check_carrier'] == "1") then
			Logger.info("[DIALPLAN] STRIPCADUP OUT")
			a = destination_number	        
			first_dig = string.sub(a, 1, 1)
			--numero iniciando por 0
			if( tonumber(first_dig)  == 0) then
			Logger.info("[DIALPLAN] first_dig 0: "..first_dig)
			cn_dest_number = string.sub(a, 2, 3)
			area_number = string.sub(a, 1, 2)
			prefix_dest_number = string.sub(a, 4, 8)
			end_dest_number = string.sub(a, 8, 12)
			carrier_dest_number = string.sub(a, 2, 12)
			-- Chamada local 8 digitos
			elseif( tonumber(first_dig)  > 0 and string.find(a,"^[2-9]%d%d%d%d%d%d%d$")) then
			Logger.info("[DIALPLAN] first_dig 8 digitos: "..first_dig)
				cn_dest_number= string.sub(callerid_number, 1, 2)
			area_number = string.sub(a, 1, 2)
			prefix_dest_number = string.sub(a, 4, 8)
			end_dest_number = string.sub(a, 8, 12)
			carrier_dest_number = cn_dest_number..a
			
			-- Chamada local 9 digitos
			elseif( tonumber(first_dig)  > 0 and string.find(a,"^[2-9]%d%d%d%d%d%d%d%d$")) then
			Logger.info("[DIALPLAN] first_dig 9 digitos: "..first_dig)
				cn_dest_number=string.sub(callerid_number, 1, 2)
			area_number = string.sub(a, 1, 2)
			prefix_dest_number = string.sub(a, 4, 8)
			end_dest_number = string.sub(a, 8, 12)
			carrier_dest_number = cn_dest_number..a
			
			-- Chamada LDN Fixo			
			elseif( tonumber(first_dig)  > 0 and string.find(a,"^[1-9]%d[2-9]%d%d%d%d%d%d%d$")) then
			Logger.info("[DIALPLAN] first_dig LDN: "..first_dig)
			cn_dest_number=51
			area_number = string.sub(a, 1, 2)
			prefix_dest_number = string.sub(a, 4, 8)
			end_dest_number = string.sub(a, 8, 12)
			carrier_dest_number = cn_dest_number..a
			else
			Logger.info("[DIALPLAN] first_dig null: "..first_dig)
			
			cn_dest_number = string.sub(a, 1, 3)
			area_number = string.sub(a, 1, 2)
			prefix_dest_number = string.sub(a, 4, 8)
			end_dest_number = string.sub(a, 8, 12)
			carrier_dest_number = string.sub(a, 2, 12)
			end
			Logger.info("=============== Carrier Information ===================")
			Logger.info("cn_dest_number : "..cn_dest_number)  
			Logger.info("area_number : "..area_number)  
			Logger.info("prefix_dest_number : "..prefix_dest_number)  
			Logger.info("end_dest_number : "..end_dest_number)
			Logger.info("carrier_dest_number : "..carrier_dest_number)
			Logger.info("destination_number : "..destination_number)
			Logger.info("callerid_number : "..callerid_number)
			userinfo['cn_dest_number'] = cn_dest_number
			Logger.info("================================================================")  	    		 
			carrier_info = get_carrier_out (userinfo,cn_dest_number,carrier_dest_number)
		end
		if(carrier_info ~= nil and carrier_info['carrier_rn1'] ~= nil) then
		-- Get termination RN1
		user_rates['rn1'] = carrier_info['carrier_rn1']
		rate_carrier_id = carrier_info['rn1']
		check_carrier = user_rates['check_carrier']
		else
		user_rates['rn1'] = 0
		rate_carrier_id = user_rates['trunk_id']
		check_carrier = 0
		end
		-- Get termination rates
		Logger.info("[DIALPLAN] User Rate RN1 : ".. user_rates['rn1'])
		Logger.info("[DIALPLAN] User Rate check_carrier : ".. check_carrier)
		Logger.info("[DIALPLAN] User Rate rate_carrier_id : ".. rate_carrier_id)
		termination_rates = get_carrier_rates (destination_number,number_loop_str_dest,userinfo['pricelist_id'],rate_carrier_id,user_rates['check_carrier'],user_rates['routing_type'])
	
	if (termination_rates ~= nil) then
	    local i = 1
	    local carrier_array = {}
	    
--	    local k = 1

	    
	    for termination_key,termination_value in pairs(termination_rates) do
		--~ if ( tonumber(termination_value['cost']) > tonumber(user_rates['cost']) ) then		    
		    	--~ Logger.info(termination_value['path']..": "..termination_value['cost'] .." > "..user_rates['cost']..", skipping")  
			    	
		if (tonumber(termination_value['cost']) > tonumber(user_rates['cost']) ) then	
			Logger.info(termination_value['path']..": "..termination_value['cost'] .." > "..user_rates['cost']..", skipping for loss less routing")
			Logger.info("=============== Termination Rates Information ===================")
			Logger.info("ID : "..termination_value['outbound_route_id'])
			Logger.info("Code : "..termination_value['pattern'])
			Logger.info("Trunk Name : "..termination_value['trunk_name'])
		--	k = k+1    	
		else
			Logger.info("=============== Termination Rates Information ===================")
			Logger.info("ID : "..termination_value['outbound_route_id'])  
			Logger.info("Code : "..termination_value['pattern'])  
			Logger.info("Destination : "..termination_value['comment'])  
			Logger.info("Connectcost : "..termination_value['connectcost'])  
			Logger.info("Free Seconds : "..termination_value['includedseconds'])  
			Logger.info("Prefix : "..termination_value['pattern'])      		    
			Logger.info("Strip : "..termination_value['strip'])      		  
			Logger.info("Prepend : "..termination_value['prepend'])      		  
			Logger.info("Trunk ID : "..termination_value['trunk_id'])  		      		    
			Logger.info("Carrier Name : "..termination_value['path'])
			Logger.info("dialplan_variable : "..termination_value['dialplan_variable']) 
			Logger.info("Failover gateway : "..termination_value['path1']) 
			Logger.info("Custom CallType : "..termination_value['call_type']) 
			Logger.info("Vendor id : "..termination_value['provider_id'])      		    		    			
			Logger.info("Max channels : "..termination_value['maxchannels'])
			if (carrier_info ~= nil and tonumber(carrier_info['rn1']) ~=nil and tonumber(carrier_info['rn1']) > 0) then
	--			if(carrier_info ~= nil and carrier_info['rn1'] ~= nil and carrier_info['rn1'] ~= '0') then
			termination_value['idCadup'] = carrier_info['idCadup']
			termination_value['carrier_name'] = carrier_info['carrier_name']
			termination_value['carrier_id'] = carrier_info['carrier_id']			
			termination_value['carrier_rn1'] = carrier_info['carrier_rn1']
			termination_value['call_count'] = carrier_info['call_count']
			termination_value['carrier_route_id'] = carrier_info['id']						
			termination_value['nomeLocalidade'] = carrier_info['nomeLocalidade']
			termination_value['nomePrestadora'] = carrier_info['nomePrestadora']
			termination_value['tipo'] = carrier_info['tipo']
			if(termination_value['tipo'] == 'M' ) then
			termination_value['tipo'] = 'Movel'
			else
			termination_value['tipo'] = 'Fixo'
			end	
			termination_value['prefixo'] = carrier_info['prefixo']
			termination_value['areaLocal'] = carrier_info['areaLocal']
			termination_value['codArea'] = carrier_info['codArea']
			termination_value['uf'] = carrier_info['uf']
			termination_value['carrier_id'] = carrier_info['id']
			termination_value['rn1'] = carrier_info['rn1']						
			if (termination_value['rn1'] ~= nil and termination_value['rn1'] ~= '0') then
            force_outbound_routes = termination_value['rn1'];
            Logger.info("force_outbound_routes : "..force_outbound_routes);
            end
			Logger.info("idCadup : "..termination_value['idCadup']) 
			Logger.info("carrier_id : "..termination_value['carrier_id'])      		  
			Logger.info("nomeLocalidade : "..termination_value['nomeLocalidade'])
			Logger.info("nomePrestadora : "..termination_value['nomePrestadora'])  		  
			Logger.info("areaLocal : "..termination_value['areaLocal'])  		      		    
			Logger.info("tipo : "..termination_value['tipo'])      		  
			Logger.info("prefixo : "..termination_value['prefixo'])
			Logger.info("codArea : "..termination_value['codArea'])  		  
			Logger.info("uf : "..termination_value['uf'])			
			Logger.info("carrier_name : "..termination_value['carrier_name'])
			Logger.info("carrier_id : "..termination_value['carrier_id'])
			Logger.info("carrier_rn1 : "..termination_value['carrier_rn1'])
			Logger.info("call_count : "..termination_value['call_count'])
			Logger.info("carrier_route_id: "..termination_value['carrier_route_id'])						  		
			Logger.info("rn1 : "..termination_value['rn1'])			
			end	    		
			custom_calltype = termination_value['comment'];
			call_typecustom = termination_value['call_type'];
			userinfo['call_type_custom'] = call_typecustom;
			Logger.info("Call Type : "..custom_calltype);
			Logger.info("User Call Type : "..userinfo['call_type_custom']);
--			termination_value['trunk_name'] = termination_value['path'];
			Logger.info("Trunk Name : "..termination_value['trunk_name'])			
			termination_value['intcall']=customer_userinfo['international_call']
			Logger.info("========================END OF TERMINATION RATES=======================")
			carrier_array[i] = termination_value
			i = i+1
		end
	    end -- For EACH END HERE
	    
		-- If we get any valid carrier rates then build dialplan for outbound call
		 if (i > 1) then
		--if (i > 1 or k > 1) then
            callerid = get_override_callerid(customer_userinfo,callerid_name,callerid_number)
            if (callerid['cid_name'] ~= nil) then
                callerid_array['cid_name'] = callerid['cid_name']
                callerid_array['cid_number'] = callerid['cid_number']
                callerid_array['original_cid_name'] = callerid['cid_name']
                callerid_array['original_cid_number'] = callerid['cid_number']
                callerid_array['company_name'] = callerid['company_name']
            end 
			xml = freeswitch_xml_header(xml,destination_number,accountcode,maxlength,call_direction,accountname,xml_user_rates,customer_userinfo,config,nil,reseller_cc_limit,callerid_array,original_destination_number)

			-- Added code to override callerid
            --xml = override_callerid_management(xml,customer_userinfo)

			--~ for carrier_arr_key,carrier_arr_array in pairs(carrier_array) do
			    --~ xml = freeswitch_xml_outbound(xml,destination_number,carrier_arr_array,callerid_array,livecall_data)
			--~ end
			local j =1;
			rate_group_id = userinfo['pricelist_id']
			call_type = userinfo['calltype']
			call_type_custom = userinfo['call_type_custom']
			for carrier_arr_key,carrier_arr_array in pairs(carrier_array) do
				old_trunk_id =0
				if(j > 1) then
					old_trunk_id =carrier_array[tonumber(j)-1]['trunk_id']
				end
				rate_group_details = get_pricelists(userinfo)				
	xml = freeswitch_xml_outbound(xml,destination_number,carrier_arr_array,callerid_array,rate_group_id,old_trunk_id,force_outbound_routes,call_type,call_type_custom,rate_group_details['routing_type'],livecall_data)
	--xml = freeswitch_xml_outbound(xml,destination_number,carrier_arr_array,callerid_array,rate_group_id,old_trunk_id,force_outbound_routes,rate_group_details['routing_type'],livecall_data)
				j=j+1;
			end			

		    xml = freeswitch_xml_footer(xml)
		else
			-- If no route found for outbound call then send no result dialplan for further process in fs
			Logger.info("[Dialplan] No termination rates found...!!!");
			error_xml_without_cdr(destination_number,"TERMINATION_RATE_NOT_FOUND",calltype,config['playback_audio_notification'],customer_userinfo['id']) 
			return
		end  --- IF ELSE END HERE
		XML_STRING = table.concat(xml, "\n");
		Logger.info("[Dialplan] Generated XML:\n" .. XML_STRING)  
	else
		Logger.info("[Dialplan] No termination rates found...!!!");
		error_xml_without_cdr(destination_number,"TERMINATION_RATE_NOT_FOUND",calltype,config['playback_audio_notification'],customer_userinfo['id']);
		return
	end
    end
else
	error_xml_without_cdr(destination_number,"ACCOUNT_INACTIVE_DELETED",calltype,config['playback_audio_notification'],customer_userinfo['id']);
	return
end
