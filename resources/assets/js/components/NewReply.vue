<template>
<div>
    <div v-if="singnedIn">
        <div class="form-group">
            <textarea name="body"
                      id="body"
                      class="form-control"
                      placeholder="Have something to say?"
                      rows="5"
                      v-model="body"
                      required></textarea>
        </div>

        <button type="submit" class="btn btn-default" @click="addReply">Post</button>
    </div>

    <div v-else="">
        <p class="text-center">Please <a href="/">sign in</a> to participate in this
            discussion.</p>
    </div>
</div>
</template>

<script>

    import 'jquery.caret';
    import 'at.js';

    export default {

        data(){
          return {
              body: '',
          }
        },
        computed: {
            singnedIn() {
                return window.App.signedIn;
            },
        },

        mounted() {
            $('#body').atwho({
                at: "@",
                delay: 750,
                callbacks: {
                    remoteFilter: function(query, callback) {
                        $.getJSON("/api/users", {name: query}, function(usernames) {
                            callback(usernames)
                        });
                    }
                }
            });
        },

        methods:{
            addReply(){
                axios.post(location.pathname + '/replies', {body: this.body})
                    .catch(error => {
                        flash(error.response.data, 'danger');
                    })
                    .then(response => {

                        let data = response.data;
                        this.body = '';
                        flash('Your reply has been posted.');

                        this.$emit('created', data);
                    });
            }
        }

    }
</script>