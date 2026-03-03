define([
    'uiComponent',
    'ko',
    'jquery'
], function (Component, ko, $) {
    'use strict';

    return Component.extend({
        defaults: {
            conversationId: null,
            iri: '',
            messagesUrl: '',
            sendMessageUrl: '',
            markReadUrl: '',
            messages: [],
            messageText: '',
            isLoading: false,
            isSending: false,
            readObserver: null,
            pendingReadIds: [],
            readDebounceTimer: null,

            tracks: {
                messages: true,
                messageText: true,
                isLoading: true,
                isSending: true
            }
        },

        initialize: function () {
            this._super();
            this.loadMessages();
            this.connectMercure();

            return this;
        },

        loadMessages: function () {
            const self = this;
            this.isLoading = true;

            $.ajax({
                url: this.messagesUrl,
                type: 'GET',
                data: { id: this.conversationId, page: 1 },
                dataType: 'json',
                showLoader: false
            }).done(function (data) {
                self.messages = data;
                self.isLoading = false;
                self.scrollToBottom();
                self.initReadObserver();
            }).fail(function () {
                self.isLoading = false;
            });
        },

        markAsRead: function (messageIds) {
            if (!messageIds || !messageIds.length) return;

            $.ajax({
                url: this.markReadUrl,
                type: 'POST',
                data: {
                    id: this.conversationId,
                    form_key: window.FORM_KEY,
                    messageIds: messageIds
                },
                dataType: 'json'
            });
        },

        initReadObserver: function () {
            var self = this;

            if (this.readObserver) {
                this.readObserver.disconnect();
            }

            this.pendingReadIds = [];
            clearTimeout(this.readDebounceTimer);

            var container = document.getElementById('livechat-messages');

            if (!container) return;

            this.readObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) return;

                    var id = parseInt(entry.target.getAttribute('data-message-id'), 10);
                    if (self.pendingReadIds.indexOf(id) === -1) {
                        self.pendingReadIds.push(id);
                    }

                    self.readObserver.unobserve(entry.target);
                });

                if (self.pendingReadIds.length) {
                    clearTimeout(self.readDebounceTimer);
                    self.readDebounceTimer = setTimeout(function () {
                        var ids = self.pendingReadIds.slice();
                        self.pendingReadIds = [];
                        self.markAsRead(ids);
                    }, 300);
                }
            }, { root: container });

            setTimeout(function () {
                self.observeUnreadMessages();
            }, 150);
        },

        observeUnreadMessages: function () {
            if (!this.readObserver) return;

            var self = this,
                container = document.getElementById('livechat-messages');

            if (!container) return;

            // sender_type 2 = admin; observe customer messages (not admin)
            container.querySelectorAll('[data-sender-type][data-message-id]').forEach(function (el) {
                if (parseInt(el.getAttribute('data-sender-type'), 10) === 2) return;

                var id = parseInt(el.getAttribute('data-message-id'), 10),
                    msg = self.messages.find(function (m) { return parseInt(m.id, 10) === id; });

                if (msg && parseInt(msg.status, 10) !== 1) {
                    self.readObserver.observe(el);
                }
            });
        },

        sendMessage: function () {
            const self = this,
                text = this.messageText.trim();

            if (!text || this.isSending) {
                return;
            }

            this.isSending = true;

            $.ajax({
                url: this.sendMessageUrl,
                type: 'POST',
                data: { id: this.conversationId, text: text, form_key: window.FORM_KEY },
                dataType: 'json'
            }).done(function () {
                self.messageText = '';
            }).fail(function () {
                // Error is displayed by Magento's global error handler
            }).always(function () {
                self.isSending = false;
            });
        },

        connectMercure: function () {
            var self = this;

            window.addEventListener('admin-mercure:message:receive', function (event) {
                var data = event.detail;

                if (parseInt(data.conversation_id, 10) === self.conversationId) {
                    self.messages.push(data);
                    self.messages = self.messages.slice();
                    self.scrollToBottom();
                    setTimeout(function () {
                        self.observeUnreadMessages();
                    }, 150);
                }
            });

            window.addEventListener('admin-mercure:message:read', function (event) {
                var data = event.detail;

                if (parseInt(data.conversation_id, 10) !== self.conversationId) {
                    return;
                }

                var messageIds = data.message_ids || [];

                self.messages.forEach(function (msg) {
                    if (messageIds.indexOf(parseInt(msg.id, 10)) !== -1) {
                        msg.status = 1;
                    }
                });

                self.messages = self.messages.slice();
            });

            window.mercureWorkerPort.postMessage({
                type: 'subscribe',
                topics: [this.iri]
            });
        },

        scrollToBottom: function () {
            setTimeout(function () {
                var container = document.getElementById('livechat-messages');

                if (container) {
                    container.scrollTop = container.scrollHeight;
                }
            }, 100);
        },

        showDateSeparator: function (index) {
            const messages = this.messages,
                current = new Date(messages[index].created_at + ' UTC').toDateString();

            if (index === 0) {
                return true;
            }

            const previous = new Date(messages[index - 1].created_at + ' UTC').toDateString();

            return current !== previous;
        },

        formatSeparatorDate: function (dateStr) {
            if (!dateStr) {
                return '';
            }

            var date = new Date(dateStr + ' UTC');

            return date.toLocaleDateString('en-US', {
                month: 'long',
                day: 'numeric'
            });
        },

        formatTime: function (dateStr) {
            if (!dateStr) {
                return '';
            }

            var date = new Date(dateStr + ' UTC');

            return date.toLocaleString('en-US', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true
            });
        },

        isAdminMessage: function (message) {
            // sender_type 2 = admin (UserContextInterface::USER_TYPE_ADMIN)
            return parseInt(message.sender_type, 10) === 2;
        },

        isRead: function (message) {
            return parseInt(message.status, 10) === 1;
        },

        handleKeydown: function (data, event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                this.sendMessage();

                return false;
            }

            return true;
        }
    });
});
