/*
** Zabbix
** Copyright (C) 2001-2014 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/

#include "control.h"

int	get_log_level_message(const char *opt, int *message, int command)
{
	int	num = 0, scope, data;

	if ('\0' == *opt)
	{
		scope = ZBX_RTC_LOG_SCOPE_FLAG | ZBX_RTC_LOG_SCOPE_PID;
		data = 0;
	}
	else if ('=' != *opt)
	{
		zbx_error("unknown log level control option: %s", opt);
		return FAIL;
	}
	else if (0 != isdigit(*(++opt)))
	{
		if (FAIL == is_ushort(opt, &num))
		{
			zbx_error("invalid log level control option: proccess identifier must be unsigned short value");
			return FAIL;
		}

		scope = ZBX_RTC_LOG_SCOPE_FLAG | ZBX_RTC_LOG_SCOPE_PID;
		data = num;
	}
	else
	{
		char	*proc_name = NULL, *ptr;
		int	proc_type;

		proc_name = zbx_strdup(proc_name, opt);

		if (NULL != (ptr = strchr(proc_name, ',')))
		{
			*ptr++ = '\0';
			if (FAIL == is_ushort(ptr, &num))
			{
				zbx_error("invalid log level control option: proccess number must be unsigned short"
						" value");
				return FAIL;
			}

			if (0 == num)
			{
				zbx_error("invalid log level control option: proccess number cannot be zero");
				return FAIL;
			}
		}

		if (ZBX_PROCESS_TYPE_UNKNOWN == (proc_type = get_process_type_by_name(proc_name)))
		{
			zbx_error("invalid log level control option: unknown process type");
			return FAIL;
		}

		zbx_free(proc_name);

		scope = ZBX_RTC_LOG_SCOPE_PROC | proc_type;
		data = num;
	}

	*message = MAKE_TASK(command, scope, data);

	return SUCCEED;
}
