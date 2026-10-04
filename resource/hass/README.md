#Vbot Assistant

## Hỏi lại khi có nhiều thiết bị

Với lệnh bật/tắt có nhiều đích phù hợp, VBot đọc tên các thiết bị và chờ chọn một thiết bị. Ví dụ: “bật đèn phòng khách” → VBot hỏi chọn đèn → trả lời “đèn phòng khách 2” hoặc “số 2” nếu tên thiết bị có số đó. VBot giữ hành động bật của câu trước và chỉ điều khiển đích được xác định duy nhất.

Ngữ cảnh hết hạn sau 30 giây kể từ lúc đọc xong câu hỏi; hai lần trả lời không xác định được đích sẽ hủy lệnh. Nói “hủy” để bỏ lệnh chờ, hoặc nói một lệnh mới đầy đủ để thay thế. Danh sách được giới hạn 10 thiết bị; trường hợp nhiều hơn cần nói tên cụ thể. Lệnh nhóm được cho phép rõ ràng vẫn xử lý theo cấu hình Area.

Câu trả lời chọn thiết bị hỗ trợ tên có dấu hoặc không dấu theo cùng cách chuẩn hóa của bộ tìm kiếm. Số trong tên phải khớp để tránh chọn nhầm giữa thiết bị 1 và 2.

Trước khi thực hiện, VBot đọc lại trạng thái và kiểm tra quyền điều khiển. Thiết bị bị chặn hoặc đã bị xóa không được thay bằng một thiết bị gần giống. Ngữ cảnh tách riêng giữa loa và từng client/phiên; không được lưu qua khởi động lại.

Ở lượt chọn thiết bị, nếu trạng thái đã bật/tắt đúng yêu cầu thì VBot chỉ thông báo “đã bật/tắt sẵn” và ghi log, không gửi lại lệnh. Nếu trạng thái khác yêu cầu, không xác định, không khả dụng hoặc thiếu trạng thái thì VBot vẫn gửi hành động đã lưu. WebUI hiển thị trạng thái trước điều khiển và quyết định có cần gửi lệnh.

Trong Home Assistant Assist, chọn chế độ `processing` và luồng `api`; custom gửi `session_id` và dùng `needs_clarification` để tiếp tục nghe. API văn bản muốn dùng hỏi lại phải gửi cùng `session_id` ở cả hai lượt. API không có mã phiên không giữ hội thoại. Tính năng hiện áp dụng cho bật/tắt, nhiệt độ điều hòa và độ sáng đèn; chưa áp dụng cho tốc độ quạt hoặc các lệnh tùy chỉnh.

## Giữ độ sáng khi chọn đèn

Nói “đặt độ sáng đèn phòng khách 50%”, sau đó chọn “đèn phòng khách 2”. VBot giữ 50%, đọc lại quyền và khả năng chỉnh độ sáng, rồi gửi `light.turn_on` với `brightness_pct: 50` chỉ cho đèn được chọn. Số 2 trong tên không thay giá trị 50%. Hỗ trợ cả câu “50 phần trăm”; phần trăm phải từ 0 đến 100 và 0% tắt đèn.

Đèn chỉ có chế độ màu `onoff` bị từ chối. Khả năng chỉnh sáng được xác định từ `supported_color_modes`, hoặc dữ liệu brightness/bit hỗ trợ của tích hợp cũ khi chưa có thông tin chế độ màu. Đèn đang bật và đã đúng mức sáng thì chỉ thông báo; đèn đang tắt nhưng còn lưu brightness cũ vẫn cần gửi lệnh khi đặt mức lớn hơn 0. Sai khác một đơn vị trên thang 0–255 được coi là cùng độ sáng vì quy đổi từ phần trăm có làm tròn. Trạng thái unknown/unavailable vẫn gửi lệnh nếu khả năng chỉnh sáng được xác nhận. Log và WebUI hiển thị giá trị, đích, lý do từ chối và kết quả.

## Giữ nhiệt độ khi chọn điều hòa

Nói “đặt điều hòa phòng ngủ 26 độ”; nếu có nhiều đích, VBot hỏi thiết bị nào cần đặt 26 độ. Trả lời “điều hòa phòng ngủ 2” chỉ chọn đích, không thay giá trị đã lưu bằng số 2 trong tên. VBot đọc lại quyền điều khiển, trạng thái và `min_temp`/`max_temp` trước khi gửi dịch vụ `climate.set_temperature`. Giữ hành vi của luồng đặt nhiệt độ hiện có: gửi `hvac_mode: cool` cùng nhiệt độ.

Câu “bật điều hòa 27 độ” cũng được xử lý như đặt nhiệt độ: tách “27 độ” khỏi tên tìm kiếm rồi gửi `set_temperature` với 27 và chế độ cool. Nếu không có số kèm đơn vị nhiệt độ thì giữ cơ chế bật thông thường, tránh lấy số trong tên thiết bị làm nhiệt độ. Khi trợ lý fallback đã xử lý yêu cầu, luồng điều khiển dừng lượt đó, không tiếp tục tìm qua nhóm thiết bị khác.

Nếu nhiệt độ đặt đã bằng 26 và điều hòa đang ở chế độ cool, chỉ thông báo đã đặt sẵn. Nếu đang off hoặc trạng thái không xác định, vẫn gửi lệnh. Nhiệt độ ngoài giới hạn thiết bị bị từ chối và ghi log. Hủy, hết hạn, đổi lệnh đầy đủ hoặc đổi chế độ thử/thực thi sẽ bỏ giá trị chờ cùng ngữ cảnh.

## Kiểm tra hội thoại trên WebUI

Trong `Command_Test.php`, bật **Đọc danh sách thiết bị**, nhập lệnh bật/tắt hoặc đặt nhiệt độ còn mơ hồ rồi nhập tên thiết bị ở lượt sau trong cùng ô câu lệnh. Thẻ hội thoại hiển thị lệnh gốc, hành động, giá trị đã lưu, danh sách lựa chọn, thời gian còn lại, đích được chọn và lý do từ chối. Chế độ thử dùng cùng cơ chế chọn đích nhưng không gửi lệnh điều khiển hoặc phát TTS.

Mỗi tab có ngữ cảnh riêng, tách khỏi loa và Assist. Chọn **Hủy ngữ cảnh** để bỏ lệnh chờ; **Bắt đầu phiên mới** còn xóa lịch sử trên trang. Đổi chế độ hủy ngữ cảnh cũ; muốn thực thi phải nhập lại lệnh từ đầu trong chế độ thực thi. Kết quả gửi dịch vụ thành công không xác nhận trạng thái vật lý của thiết bị.


### Fan speed dialogue

Set fan speed to 50%, then choose a specific fan. The stored percentage is preserved while fresh permissions, state and SET_SPEED capability are checked. Values must be 0 to 100. Zero sends `fan.turn_off`; a positive value sends `fan.set_percentage` with `percentage`, using the advertised SET_SPEED capability even when the fan is off. An on fan already at that percentage is skipped. Unknown state still executes when speed capability is confirmed. Switch entities are excluded. Vietnamese and English percent/speed terms are supported, and WebUI dry runs show the actual service and payload without executing.

### Độ mở rèm và hỏi lại thiết bị

Nói “mở rèm phòng khách 50%” hoặc “đặt độ mở rèm phòng khách 50%”, rồi chọn “rèm phòng khách 2”. VBot giữ phần trăm từ câu đầu, đọc lại trạng thái và quyền điều khiển, kiểm tra `SET_POSITION`, rồi gửi `cover.set_cover_position` với `position: 50`. Hỗ trợ phần trăm theo ngôn ngữ đã chọn; giá trị phải từ 0 đến 100. 0% đóng hoàn toàn, 100% mở hoàn toàn.

Chỉ bỏ qua khi rèm đã dừng và đúng vị trí yêu cầu. Rèm đang mở/đóng hoặc trạng thái không xác định vẫn nhận lệnh nếu hỗ trợ đặt vị trí. Rèm chỉ mở/đóng, không hỗ trợ đặt vị trí, bị từ chối. “Dừng rèm” hủy yêu cầu hỏi lại đang chờ và đi theo luồng `cover.stop_cover`; không cần đọc lại vị trí để gửi lệnh dừng. Phản hồi có trong log và chế độ thử trên `Command_Test.php`.


## Optional post-control state verification

Set `home_assistant.verify_after_control` to JSON `true` or `false` (default: `false`). The HASS section in Config.php provides the corresponding switch. Save and restart VBot. Old configurations without the setting keep the previous behavior and perform no extra state reads.

When enabled, accepted direct service calls are followed by state reads for at most 3 seconds per entity. Light brightness, fan percentage, cover position, on/off state and climate mode/setpoint are checked. Climate verification checks the requested setpoint, not the current room temperature. A moving cover is reported separately; unknown/unavailable state, delayed updates, failed reads or timeout produce an unconfirmed result. This feature never resends an accepted service call and does not use assistant fallback for observation failure.

Confirmation reflects the state reported by Home Assistant. Script/automation, media-player and unsupported services only report that the command was sent. Custom service commands bypassing the direct control helper are not verified. Area groups retain the existing 45-second total budget and 5-second per-target budget; verification does not extend these limits. Logs and real execution on Command_Test.php include requested data, observed state/attributes, polling count and verification result. Dry runs only display the setting and pre-control state; they perform no control or post-control verification.


## Optional pre-control state check

`home_assistant.check_state_before_control` is a JSON boolean, default `true`. Config.php provides a separate switch above the post-control verification setting. With `true`, an already-matching state or value skips the command. Unknown/unavailable state or a failed bounded state read still sends the requested action, including turn-off requests. With `false`, every valid requested action is sent, even when the current state already matches. Permissions, target disambiguation, capability checks and value limits remain enforced. Existing configurations missing the property retain the enabled behavior. Save and restart VBot.

The setting applies to direct commands, clarification follow-ups, Area groups and direct MCP control. Pre-control and post-control settings are independent: disabling the former does not disable post-control verification. The stop-cover command is always sent immediately after resolving its target; it is never skipped because a cover appears stationary. Custom service actions bypassing the direct control helper keep their existing behavior. Command_Test.php previews respect the pre-control setting but never execute or perform post-control verification.

## Lost service-call responses

If a service POST times out or loses its response, VBot reports `delivery_unknown`: Home Assistant may already have received the action. Direct and custom control calls do not try another URL in this case. Direct control also stops assistant fallback, and a matched custom command that fails stops normal command dispatch. A connection failure before establishing the connection can still use another configured URL. Check the device before manually sending the action again. This protection applies independently of both state-check settings.
