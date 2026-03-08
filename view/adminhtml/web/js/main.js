define([
    'uiComponent',
    'mage/translate'
], function (Component, $t) {
    'use strict';

    return Component.extend({
        defaults: {
            open: false,
            isLoading: false,
            layout: 'main',
            message: null,
            currentConversation: null,
            conversations: [],
            currentPage: 1,
            currentConversationPage: 0,
            hasMoreMessages: false,
            hasMoreConversations: true,
            isLoadingMore: false,
            conversationTitleDateFormatter: null,
            messageDateFormatter: null,
            lastMessageDateFormatter: null,
            separatorDateFormatter: null,
            adminSenderId: null,
            currentPhrase: {
                emoji: '🙏',
                text: 'Seems someone needs your help'
            },
            endpoint: {
                messages: '',
                sendMessage: '',
                markAsRead: ''
            },

            tracks: {
                open: true,
                layout: true,
                isLoading: true,
                conversations: true,
                currentConversation: true,
                messages: true,
                message: true,
                currentPage: true,
                hasMoreMessages: true,
                hasMoreConversations: true
            }
        },

        async initialize() {
            this._super();
            await this.loadConversations();
            this.layout = this.conversations.length ? 'main' : 'welcome'

            this.initMercure();
        },

        async loadConversations() {
            if (
                this.isLoadingMore
                || !this.hasMoreConversations
            ) {
                return;
            }

            this.isLoadingMore = true;
            const nextPage = this.currentConversationPage + 1;

            const response = await fetch(
                `${this.endpoint.index}?page=${nextPage}`,
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            if (!response.ok) {
                this.isLoadingMore = false;
                console.error('Error loading more conversations');
                return;
            }

            const conversations = await response.json();
            this.currentConversationPage = nextPage;
            this.hasMoreConversations = conversations?.length >= 10;
            this.isLoadingMore = false;
            if (!conversations.length) {
                return;
            }

            this.conversations.push(...conversations);
            this.layout = this.conversations.length ? 'main' : this.layout;
        },

        initMercure() {
            window.mercureWorkerPort.postMessage({
                type: 'subscribe',
                topics: [`${window.origin}/livechat/{id}`]
            });

            for (const event in this.eventListeners) {
                window.addEventListener(event, this.eventListeners[event].bind(this));
            }
        },

        openConversation(conversationId) {
            this.currentConversation = this.conversations.find(({ id }) => +id === +conversationId);
            this.currentPage = 1;
            this.layout = 'conversation';
            this.hasMoreMessages = this.currentConversation.messages.length >= 25
            this.markAsRead();
        },

        async markAsRead() {
            const result = await fetch(
                `${this.endpoint.markAsRead}?id=${this.currentConversation.id}`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            if (!result.ok) {
                return console.log('Something went wrong during mark as read');
            }
        },

        async loadMoreMessages() {
            if (
                this.isLoadingMore
                || !this.currentConversation
                || !this.hasMoreMessages
            ) {
                return;
            }

            const { id: conversationId } = this.currentConversation;
            this.isLoadingMore = true;
            const nextPage = this.currentPage + 1;

            const response = await fetch(
                `${this.endpoint.messages}?id=${conversationId}&page=${nextPage}`,
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }
            );

            if (!response.ok) {
                this.isLoadingMore = false;
                console.error('Error loading more messages');
                return;
            }

            const messages = await response.json();
            this.currentPage = nextPage;
            if (!messages.length) {
                return;
            }

            this.hasMoreMessages = messages.length >= 25;
            this.isLoadingMore = false;

            const conversation = this.conversations?.find(({ id }) => id === conversationId)
            conversation.messages.unshift(...messages);
            this.conversations.replace(conversation, {...conversation});
            if (+this.currentConversation?.id === +conversation.id) {
                this.currentConversation = conversation;
            }
        },

        async sendMessage(element) {
            if (!this.message) {
                return;
            }

            this.isLoading = true;

            const response = await fetch(
                this.endpoint.sendMessage,
                {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: new FormData(element)
                }
            );

            this.isLoading = false;
            if (!response.ok) {
                console.error('Error during fetch all customer conversations')
                return;
            }

            this.message = null;
        },

        sortConversations() {
            this.conversations.sort((a, b) => {
                const firstDate = b.messages.at(-1)?.created_at || b.created_at;
                const secondDate = a.messages.at(-1)?.created_at || a.created_at;

                return new Date(firstDate).getTime() - new Date(secondDate).getTime();
            });
        },

        showDateSeparator(index) {
            const current = new Date(this.currentConversation.messages[index].created_at + ' UTC').toDateString();
            if (index === 0) {
                return true;
            }

            const previous = new Date(this.currentConversation.messages[index - 1].created_at + ' UTC').toDateString();

            return current !== previous;
        },

        formatSeparatorDate(date) {
            if (!this.separatorDateFormatter) {
                this.separatorDateFormatter = new Intl.DateTimeFormat('en-US', {
                    month: 'long',
                    day: 'numeric'
                });
            }

            return this.separatorDateFormatter.format(new Date(date + ' UTC'))
        },

        formatMessageDate(date) {
            if (!this.messageDateFormatter) {
                this.messageDateFormatter = new Intl.DateTimeFormat('en-US', {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });
            }

            return this.messageDateFormatter.format(new Date(date + ' UTC'))
        },

        formatConversationTitle(date) {
            if (!this.conversationTitleDateFormatter) {
                this.conversationTitleDateFormatter = new Intl.DateTimeFormat(
                    'en',
                    { dateStyle: 'medium', timeStyle: 'short' }
                );
            }

            return `<span aria-hidden="true">💬</span> ${$t('Started')} `
                + this.conversationTitleDateFormatter.format(new Date(date + ' UTC'));
        },

        formatLastMessageDate(conversation) {
            const date = conversation.messages.at(-1)?.created_at;
            if (!date) {
                return '';
            }

            if (!this.lastMessageDateFormatter) {
                this.lastMessageDateFormatter = new Intl.RelativeTimeFormat("en", { numeric: "auto", style: "narrow" });
            }

            const secondsDiff = Math.round((new Date(date + ' UTC') - Date.now()) / 1000);
            const unitsInSec = [60, 3600, 86400, 86400 * 7, 86400 * 30, 86400 * 365, Infinity],
                unitStrings = ["second", "minute", "hour", "day", "week", "month", "year"],
                unitIndex = unitsInSec.findIndex((cutoff) => cutoff > Math.abs(secondsDiff));

            const divisor = unitIndex ? unitsInSec[unitIndex - 1] : 1;
            return this.lastMessageDateFormatter.format(
                Math.floor(secondsDiff / divisor),
                unitStrings[unitIndex]
            );
        },

        formatConversationMessagePreview(conversation) {
            const message = conversation.messages.at(-1);
            if (!message) {
                return $t('No messages yet');
            }

            return (+message.sender_type === this.adminSenderId ? $t('You') : message.sender_name) + ': ' + message.text;
        },

        getConversationLabel(conversation) {
            return this.formatConversationTitle(conversation.created_at).replace(/<[^>]*>/g, '')
                + '. '
                + this.formatConversationMessagePreview(conversation);
        },

        getConversationUnread(conversation) {
            return conversation.messages.filter(
                item => +item.id > +conversation.last_admin_read_message_id
                    && +item.sender_type !== +this.adminSenderId
            ).length;
        },

        getTotalUnread() {
            return this.conversations?.reduce(
                (acc, conversation) => acc + conversation.messages.filter(
                    item => +item.id > +conversation.last_admin_read_message_id
                        && +item.sender_type !== +this.adminSenderId
                ).length,
                0
            );
        },

        eventListeners: {
            ['mercure:message:receive'](event) {
                const message = event.detail;
                const { conversation_id: conversationId } = message;

                const conversation = this.conversations.find(({ id }) => +id === +conversationId);
                if (!conversation) {
                    return;
                }

                if (+message.sender_type !== +this.adminSenderId) {
                    conversation.total_unread = (conversation.total_unread || 0) + 1;
                }

                conversation.messages.push(message);
                this.conversations.replace(conversation, {...conversation});
                if (+this.currentConversation?.id === +conversation.id) {
                    this.currentConversation = conversation;
                    if (+message.sender_type !== +this.adminSenderId) {
                        this.markAsRead();
                    }
                }

                this.sortConversations();
            },

            ['mercure:conversation:read'](event) {
                const {
                    last_admin_read_message_id: lastAdminReadId,
                    last_user_read_message_id: lastUserReadId,
                    conversation_id: conversationId
                } = event.detail;

                const conversation = this.conversations.find(({ id }) => +id === +conversationId);
                if (!conversation) {
                    return;
                }

                conversation.last_admin_read_message_id = lastAdminReadId;
                conversation.last_user_read_message_id = lastUserReadId;
                this.conversations.replace(conversation, {...conversation});
                if (+this.currentConversation?.id === +conversation.id) {
                    this.currentConversation = conversation;
                }
            },

            ['mercure:conversation:create'](event) {
                const { id: conversationId } = event.detail,
                    conversation = this.conversations.find(({ id }) => id === +conversationId);

                if (conversation) {
                    return;
                }

                event.detail.id = +event.detail.id;
                event.detail.messages = [];
                this.conversations.push(event.detail);
                this.sortConversations();

                if (this.pendingConversationId === event.detail.id) {
                    this.openConversation(event.detail.id);
                    this.pendingConversationId = null;
                }
            }
        }
    });
});
